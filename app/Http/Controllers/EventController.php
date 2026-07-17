<?php

namespace App\Http\Controllers;

use App\Exports\EventExport;
use App\Http\Requests\EventFormRequest;
use App\Models\Event;
use App\Models\Kategori;
use App\Models\lokasi;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['kategori', 'tickets', 'lokasi']);

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy('tanggal_waktu', $sort);

        $events = $query->paginate(10);
        $categories = Kategori::all();
        $locations = lokasi::all()->where('aktif', 'Y');

        return view('pages.admin.events.index', compact('events', 'categories', 'locations', 'sort'));
    }

    public function create()
    {
        $categories = Kategori::all();
        $locations = lokasi::all()->where('aktif', 'Y');

        return view('pages.admin.events.create', compact('categories', 'locations'));
    }

    public function store(EventFormRequest $request)
    {
        $gambar = $request->hasFile('gambar')
            ? $request->file('gambar')->store('events', 'public')
            : 'konser.jpg';

        $event = Event::create([
            'user_id'       => auth()->id(),
            'kategori_id'   => $request->kategori_id,
            'judul'         => $request->judul,
            'deskripsi'     => $request->deskripsi,
            'gambar'        => $gambar,
            'tanggal_waktu' => $request->tanggal_waktu,
            'lokasi_id'     => $request->lokasi_id,
        ]);

        foreach ($request->tikets as $tiket) {
            $event->tickets()->create([
                'tipe'  => $tiket['tipe'],
                'harga' => $tiket['harga'],
                'stok'  => $tiket['stok'],
            ]);
        }

        $event->recordStatus('Event dibuat');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil ditambahkan!');
    }

    public function edit(Event $event)
    {
        $categories = Kategori::all();
        $locations = lokasi::all()->where('aktif', 'Y');
        $event->load(['tickets' => fn ($q) => $q->withCount('detailOrders'), 'statusHistories']);
        $hasSales = $event->hasSales();

        return view('pages.admin.events.edit', compact('event', 'categories', 'locations', 'hasSales'));
    }

    public function update(EventFormRequest $request, Event $event)
    {
        if ($event->hasSales()) {
            $newDate = \Carbon\Carbon::parse($request->tanggal_waktu);
            if (! $newDate->equalTo($event->tanggal_waktu)) {
                return back()->withInput()->withErrors([
                    'tanggal_waktu' => 'Tanggal & waktu tidak dapat diubah karena event sudah memiliki penjualan.',
                ]);
            }
        }

        $data = [
            'kategori_id'   => $request->kategori_id,
            'judul'         => $request->judul,
            'deskripsi'     => $request->deskripsi,
            'lokasi_id'        => $request->lokasi_id,
            'tanggal_waktu' => $request->tanggal_waktu,
        ];

        if ($request->hasFile('gambar')) {
            if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
                Storage::disk('public')->delete($event->gambar);
            }
            $data['gambar'] = $this->storeCroppedImage($request->file('gambar'));
        }

        $event->update($data);

        $event->recordStatus('Event diperbarui');

        $keptIds = [];
        foreach ($request->tikets as $tiket) {
            if (! empty($tiket['id'])) {
                $existing = $event->tickets()->find($tiket['id']);
                if ($existing) {
                    $existing->update([
                        'tipe'  => $tiket['tipe'],
                        'harga' => $tiket['harga'],
                        'stok'  => $tiket['stok'],
                    ]);
                    $keptIds[] = $existing->id;
                }
            } else {
                $new = $event->tickets()->create([
                    'tipe'  => $tiket['tipe'],
                    'harga' => $tiket['harga'],
                    'stok'  => $tiket['stok'],
                ]);
                $keptIds[] = $new->id;
            }
        }

        $removed = $event->tickets()->whereNotIn('id', $keptIds)->get();
        foreach ($removed as $ticket) {
            if (! $ticket->detailOrders()->exists()) {
                $ticket->delete();
            }
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil diperbarui!');
    }

    public function destroy(Event $event)
    {
        if ($event->hasSales()) {
            return back()->with('error', 'Event tidak dapat dihapus karena sudah memiliki penjualan.');
        }

        if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
            Storage::disk('public')->delete($event->gambar);
        }

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus!');
    }

    public function clone(Event $event)
    {
        $new = $event->replicate();
        $new->judul = $event->judul.' (Copy)';
        $new->user_id = auth()->id();

        if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
            $newPath = 'events/'.uniqid('clone_').'.'.pathinfo($event->gambar, PATHINFO_EXTENSION);
            Storage::disk('public')->copy($event->gambar, $newPath);
            $new->gambar = $newPath;
        }

        $new->save();

        foreach ($event->tickets as $ticket) {
            $new->tickets()->create($ticket->only(['tipe', 'harga', 'stok']));
        }

        return redirect()->route('admin.events.edit', $new)
            ->with('success', 'Event berhasil diduplikasi. Silakan sesuaikan tanggal & detailnya.');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:events,id'],
        ]);

        $events = Event::whereIn('id', $validated['ids'])->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($events as $event) {
            if ($event->hasSales()) {
                $skipped++;

                continue;
            }

            if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
                Storage::disk('public')->delete($event->gambar);
            }

            $event->delete();
            $deleted++;
        }

        $message = "{$deleted} event berhasil dihapus.";
        if ($skipped > 0) {
            $message .= " {$skipped} event dilewati karena sudah memiliki penjualan.";
        }

        return redirect()->route('admin.events.index')->with('success', $message);
    }

    public function show(Event $event)
    {
        $event->load(['kategori', 'tickets']);

        $relatedEvents = Event::with(['kategori', 'tickets'])
            ->where('kategori_id', $event->kategori_id)
            ->where('id', '!=', $event->id)
            ->upcoming()
            ->take(4)
            ->get();

        return view('events.show', compact('event', 'relatedEvents'));
    }

    public function export(Request $request)
    {
        return Excel::download(new EventExport($request->kategori_id, $request->search), 'events-'.now()->format('Y-m-d').'.xlsx');
    }

    private function storeCroppedImage(UploadedFile $file): string
    {
        $image = Image::decode($file->getRealPath())->cover(1280, 720);
        $path = 'events/'.uniqid('event_').'.jpg';

        Storage::disk('public')->put($path, (string) $image->encodeUsingFileExtension('jpg', quality: 85));

        return $path;
    }
}

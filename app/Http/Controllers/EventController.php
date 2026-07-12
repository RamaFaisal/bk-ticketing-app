<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventFormRequest;
use App\Models\Event;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['kategori', 'tickets']);

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

        return view('pages.admin.events.index', compact('events', 'categories', 'sort'));
    }

    public function create()
    {
        $categories = Kategori::all();

        return view('pages.admin.events.create', compact('categories'));
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
            'lokasi'        => $request->lokasi,
            'gambar'        => $gambar,
            'tanggal_waktu' => $request->tanggal_waktu,
        ]);

        foreach ($request->tikets as $tiket) {
            $event->tickets()->create([
                'tipe'  => $tiket['tipe'],
                'harga' => $tiket['harga'],
                'stok'  => $tiket['stok'],
            ]);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil ditambahkan!');
    }

    public function edit(Event $event)
    {
        $categories = Kategori::all();
        $event->load(['tickets' => fn ($q) => $q->withCount('detailOrders')]);
        $hasSales = $event->hasSales();

        return view('pages.admin.events.edit', compact('event', 'categories', 'hasSales'));
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
            'lokasi'        => $request->lokasi,
            'tanggal_waktu' => $request->tanggal_waktu,
        ];

        if ($request->hasFile('gambar')) {
            if ($event->gambar && $event->gambar !== 'konser.jpg' && Storage::disk('public')->exists($event->gambar)) {
                Storage::disk('public')->delete($event->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('events', 'public');
        }

        $event->update($data);

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
}

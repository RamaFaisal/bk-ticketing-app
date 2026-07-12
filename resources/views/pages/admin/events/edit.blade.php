@extends('layouts.admin_layouts')

@section('title', 'Edit Event')

@section('content')
<div class="container mx-auto p-6 max-w-4xl">
    <a href="{{ route('admin.events.index') }}" class="btn btn-ghost btn-sm mb-4">← Kembali</a>

    <h1 class="text-3xl font-semibold mb-6">Edit Event</h1>

    @if ($hasSales)
        <div class="alert alert-warning mb-4">
            <span>Event ini sudah memiliki penjualan tiket. Beberapa field mungkin tidak dapat diubah.</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card bg-white shadow-xs mb-6">
            <div class="card-body">
                <h2 class="card-title mb-4">Informasi Event</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Judul Event</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="text" name="judul" value="{{ old('judul', $event->judul) }}" class="input input-bordered w-full" required />
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Kategori</span>
                            <span class="text-error">*</span>
                        </label>
                        <select name="kategori_id" class="select select-bordered w-full" required>
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('kategori_id', $event->kategori_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Lokasi</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="text" name="lokasi" value="{{ old('lokasi', $event->lokasi) }}" class="input input-bordered w-full" required />
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Tanggal &amp; Waktu</span>
                            <span class="text-error">*</span>
                            @if ($hasSales)
                                <span class="text-xs text-warning">(terkunci karena event sudah terjual)</span>
                            @endif
                        </label>
                        <input type="datetime-local" name="tanggal_waktu"
                               value="{{ old('tanggal_waktu', $event->tanggal_waktu->format('Y-m-d\TH:i')) }}"
                               class="input input-bordered w-full {{ $hasSales ? 'bg-gray-100' : '' }}"
                               {{ $hasSales ? 'readonly' : '' }} required />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="block">
                            <span class="text-sm font-medium">Gambar Saat Ini</span>
                        </label>
                        <img src="{{ $event->image_url }}" alt="{{ $event->judul }}" class="w-32 h-32 object-cover rounded mb-2" />
                        <input type="file" name="gambar" accept="image/*" class="file-input file-input-bordered w-full" onchange="previewImage(event)" />
                        <p class="text-xs text-gray-400">Kosongkan jika tidak ingin mengubah gambar. (maks 2MB)</p>
                        <div id="imagePreview" class="hidden mt-2">
                            <span class="text-sm text-gray-500">Preview gambar baru:</span>
                            <img src="" alt="Preview" class="w-32 h-32 object-cover rounded" />
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="block">
                            <span class="text-sm font-medium">Deskripsi</span>
                            <span class="text-error">*</span>
                        </label>
                        <textarea name="deskripsi" rows="4" class="textarea textarea-bordered w-full" required>{{ old('deskripsi', $event->deskripsi) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white shadow-xs mb-6">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <h2 class="card-title">Tiket</h2>
                    <button type="button" class="btn btn-sm btn-secondary ml-auto" onclick="addTicket()">+ Tambah Tiket</button>
                </div>

                <div id="ticketContainer" class="space-y-4"></div>
            </div>
        </div>

        @if ($event->statusHistories->isNotEmpty())
            <div class="card bg-white shadow-xs mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">Riwayat Status</h2>
                    <ul class="space-y-3">
                        @foreach ($event->statusHistories as $history)
                            @php
                                $badge = match ($history->status) {
                                    'Upcoming' => 'badge-info',
                                    'Ongoing' => 'badge-success',
                                    'Completed' => 'badge-neutral',
                                    default => 'badge-ghost',
                                };
                            @endphp
                            <li class="flex items-center gap-3">
                                <span class="badge {{ $badge }}">{{ $history->status }}</span>
                                <span class="text-sm text-gray-500">{{ $history->created_at->format('d M Y, H:i') }}</span>
                                @if ($history->note)
                                    <span class="text-sm text-gray-400">— {{ $history->note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Perbarui Event</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<script>
    let ticketIndex = 0;

    function ticketCardHTML(index, data = {}) {
        const tipe = data.tipe || 'reguler';
        const harga = data.harga ?? '';
        const stok = data.stok ?? '';
        const id = data.id ?? '';
        const sold = data.sold === true;

        const idField = id ? `<input type="hidden" name="tikets[${index}][id]" value="${id}">` : '';
        const soldBadge = sold ? `<span class="badge badge-warning ml-2">Sudah Terjual</span>` : '';
        const deleteBtn = sold
            ? ''
            : `<button type="button" class="btn btn-xs btn-error ml-auto" onclick="removeTicket(this)">Hapus</button>`;

        return `
        <div class="border rounded-lg p-4" data-ticket-card>
            <div class="flex items-center mb-3">
                <h3 class="font-medium" data-ticket-title>Tiket #${index + 1}</h3>
                ${soldBadge}
                ${deleteBtn}
            </div>
            ${idField}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="space-y-1">
                    <label class="text-sm">Tipe</label>
                    <select name="tikets[${index}][tipe]" class="select select-bordered w-full">
                        <option value="reguler" ${tipe === 'reguler' ? 'selected' : ''}>Reguler</option>
                        <option value="premium" ${tipe === 'premium' ? 'selected' : ''}>Premium</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-sm">Harga</label>
                    <input type="number" name="tikets[${index}][harga]" value="${harga}" min="0" class="input input-bordered w-full" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm">Stok</label>
                    <input type="number" name="tikets[${index}][stok]" value="${stok}" min="0" class="input input-bordered w-full" />
                </div>
            </div>
        </div>`;
    }

    function addTicket(data = {}) {
        const container = document.getElementById('ticketContainer');
        container.insertAdjacentHTML('beforeend', ticketCardHTML(ticketIndex, data));
        ticketIndex++;
        renumberTickets();
    }

    function removeTicket(btn) {
        const cards = document.querySelectorAll('[data-ticket-card]');
        if (cards.length <= 1) {
            alert('Minimal harus ada 1 tiket.');
            return;
        }
        btn.closest('[data-ticket-card]').remove();
        renumberTickets();
    }

    function renumberTickets() {
        document.querySelectorAll('[data-ticket-title]').forEach((el, i) => {
            el.textContent = 'Tiket #' + (i + 1);
        });
    }

    function previewImage(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');
        if (file) {
            preview.querySelector('img').src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    @php
        $existingTickets = $event->tickets->map(fn ($t) => [
            'id' => $t->id,
            'tipe' => $t->tipe,
            'harga' => $t->harga,
            'stok' => $t->stok,
            'sold' => $t->detail_orders_count > 0,
        ]);
    @endphp

    document.addEventListener('DOMContentLoaded', function () {
        const oldTickets = @json(old('tikets'));
        const existingTickets = @json($existingTickets);

        const source = oldTickets && oldTickets.length > 0 ? oldTickets : existingTickets;

        if (source.length > 0) {
            source.forEach(t => addTicket(t));
        } else {
            addTicket();
        }
    });
</script>
@endsection

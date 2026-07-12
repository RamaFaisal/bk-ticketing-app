@extends('layouts.admin_layouts')

@section('title', 'Tambah Event')

@section('content')
<div class="container mx-auto p-6 max-w-4xl">
    <a href="{{ route('admin.events.index') }}" class="btn btn-ghost btn-sm mb-4">← Kembali</a>

    <h1 class="text-3xl font-semibold mb-6">Tambah Event</h1>

    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card bg-white shadow-xs mb-6">
            <div class="card-body">
                <h2 class="card-title mb-4">Informasi Event</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Judul Event</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="text" name="judul" value="{{ old('judul') }}" class="input input-bordered w-full" required />
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Kategori</span>
                            <span class="text-error">*</span>
                        </label>
                        <select name="kategori_id" class="select select-bordered w-full" required>
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('kategori_id') == $category->id ? 'selected' : '' }}>
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
                        <input type="text" name="lokasi" value="{{ old('lokasi') }}" class="input input-bordered w-full" required />
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Tanggal &amp; Waktu</span>
                            <span class="text-error">*</span>
                        </label>
                        <input type="datetime-local" name="tanggal_waktu" value="{{ old('tanggal_waktu') }}" class="input input-bordered w-full" required />
                    </div>

                    <div class="space-y-2">
                        <label class="block">
                            <span class="text-sm font-medium">Gambar</span>
                            <span class="text-xs text-gray-400">(maks 2MB, opsional)</span>
                        </label>
                        <input type="file" name="gambar" accept="image/*" class="file-input file-input-bordered w-full" onchange="previewImage(event)" />
                        <div id="imagePreview" class="hidden mt-2">
                            <img src="" alt="Preview" class="w-32 h-32 object-cover rounded" />
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="block">
                            <span class="text-sm font-medium">Deskripsi</span>
                            <span class="text-error">*</span>
                        </label>
                        <textarea name="deskripsi" rows="4" class="textarea textarea-bordered w-full" required>{{ old('deskripsi') }}</textarea>
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

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Event</button>
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
        return `
        <div class="border rounded-lg p-4" data-ticket-card>
            <div class="flex items-center mb-3">
                <h3 class="font-medium" data-ticket-title>Tiket #${index + 1}</h3>
                <button type="button" class="btn btn-xs btn-error ml-auto" onclick="removeTicket(this)">Hapus</button>
            </div>
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

    document.addEventListener('DOMContentLoaded', function () {
        const oldTickets = @json(old('tikets', []));
        if (oldTickets.length > 0) {
            oldTickets.forEach(t => addTicket(t));
        } else {
            addTicket();
        }
    });
</script>
@endsection

@extends('layouts.admin_layouts')

@section('title', 'Manajemen Event')

@section('content')
<div class="container mx-auto p-6">
    <div class="flex items-center mb-6">
        <h1 class="text-3xl font-semibold">Manajemen Event</h1>
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary ml-auto">
            + Tambah Event
        </a>
    </div>

    @if (session('error'))
        <div class="alert alert-error mb-4">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.events.index') }}" class="bg-white rounded-box shadow-xs p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="label"><span class="label-text">Cari</span></label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Judul atau lokasi..." class="input input-bordered w-full" />
            </div>
            <div>
                <label class="label"><span class="label-text">Kategori</span></label>
                <select name="kategori_id" class="select select-bordered w-full">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('kategori_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label"><span class="label-text">Urutkan Tanggal</span></label>
                <select name="sort" class="select select-bordered w-full">
                    <option value="asc" {{ request('sort', 'asc') === 'asc' ? 'selected' : '' }}>Terlama</option>
                    <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>Terbaru</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.events.index') }}" class="btn btn-ghost border border-gray-300">Reset</a>
                <a href="{{ route('admin.events.export', request()->only(['kategori_id', 'search'])) }}" class="btn btn-success">
                    Export Excel
                </a>
            </div>
        </div>
    </form>

    <div class="flex items-center gap-2 mb-4 hidden" id="bulk-actions">
        <button type="button" onclick="bulkDelete()" class="btn btn-sm bg-red-500 text-white">
            Hapus Terpilih
        </button>
    </div>

    <div class="overflow-x-auto rounded-box bg-white p-5 shadow-xs">
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <label>
                            <input type="checkbox" id="select-all" class="checkbox" />
                        </label>
                    </th>
                    <th>Gambar</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    @php
                        $badge = match ($event->status) {
                            'Upcoming' => 'badge-info',
                            'Ongoing' => 'badge-success',
                            'Completed' => 'badge-neutral',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" class="checkbox row-checkbox" value="{{ $event->id }}" />
                            </label>
                        </th>
                        <td>
                            <img src="{{ $event->image_url }}" alt="{{ $event->judul }}" class="w-16 h-16 object-cover rounded" />
                        </td>
                        <td class="font-medium">{{ $event->judul }}</td>
                        <td>{{ $event->kategori->nama ?? '-' }}</td>
                        <td>{{ $event->tanggal_waktu->format('d M Y, H:i') }}</td>
                        <td>{{ $event->lokasi->nama_lokasi ?? '-' }}</td>
                        <td><span class="badge {{ $badge }}">{{ $event->status }}</span></td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-ghost border border-gray-300" target="_blank">Lihat</a>
                                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.events.clone', $event) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary">Clone</button>
                                </form>
                                <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Yakin ingin menghapus event ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm bg-red-500 text-white">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-6">Tidak ada event tersedia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $events->appends(request()->except('page'))->links() }}
        </div>
    </div>
</div>
<script>
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = () => Array.from(document.querySelectorAll('.row-checkbox'));

    function updateSelectedCount() {
        const count = rowCheckboxes().filter(cb => cb.checked).length;
        document.getElementById('bulk-actions').classList.toggle('hidden', count === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            rowCheckboxes().forEach(cb => { cb.checked = selectAll.checked; });
            updateSelectedCount();
        });
    }

    rowCheckboxes().forEach(cb => cb.addEventListener('change', () => {
        if (selectAll) {
            selectAll.checked = rowCheckboxes().every(c => c.checked);
        }
        updateSelectedCount();
    }));

    function bulkDelete() {
        const selected = rowCheckboxes().filter(cb => cb.checked);

        if (selected.length === 0) {
            alert('Pilih setidaknya satu event untuk dihapus.');
            return;
        }

        if (!confirm(`Yakin ingin menghapus ${selected.length} event?`)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.events.bulk-delete') }}';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        selected.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection

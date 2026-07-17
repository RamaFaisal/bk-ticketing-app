@extends('layouts.admin_layouts')

@section('title', 'Manajemen Lokasi')

@section('content')

    <div class="container mx-auto p-10">
        <div class="flex">
            <h1 class="text-3xl font-semibold mb-4">Manajemen Lokasi</h1>
            <button class="btn btn-primary ml-auto" onclick="add_modal.showModal()">Tambah Lokasi</button>
        </div>
        <div class="overflow-x-auto rounded-box bg-white p-5 shadow-xs">
            <table class="table">
                <!-- head -->
                <thead>
                    <tr>
                        <th>No</th>
                        <th class="w-3/4">Nama Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $index => $locate)
                    <tr>
                        <th>{{ $index + 1 }}</th>
                        <td>{{ $locate->nama_lokasi }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary mr-2" onclick="openEditModal(this)" data-id="{{ $locate->id }}" data-nama="{{ $locate->nama_lokasi }}">Edit</button>
                            <button class="btn btn-sm bg-red-500 text-white" onclick="openDeleteModal(this)" data-id="{{ $locate->id }}">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada lokasi tersedia.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Location Modal -->
    <dialog id="add_modal" class="modal">
        <form method="POST" action="{{ route('admin.locations.store') }}" class="modal-box">
            @csrf
            <h3 class="text-lg font-bold mb-4">Tambah Lokasi</h3>
            <div class="form-control w-full mb-4">
                <label class="label mb-2">
                    <span class="label-text">Nama Lokasi</span>
                </label>
                <input type="text" placeholder="Masukkan nama lokasi" class="input input-bordered w-full" name="nama_lokasi" required />
            </div>
            <div class="modal-action">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <button class="btn" onclick="add_modal.close()" type="reset">Batal</button>
            </div>
        </form>
    </dialog>

    <!-- Edit Location Modal With Retrieve ID -->
     <dialog id="edit_modal" class="modal">
        <form method="POST" class="modal-box">
            @csrf
            @method('PUT')

            <input type="hidden" name="location" id="edit_location_id">

            <h3 class="text-lg font-bold mb-4">Edit Lokasi</h3>
            <div class="form-control w-full mb-4">
                <label class="label mb-2">
                    <span class="label-text">Nama Lokasi</span>
                </label>
                <input type="text" placeholder="Masukkan nama lokasi" class="input input-bordered w-full" value="Lokasi Contoh" id="edit_location_name" name="nama_lokasi" />
            </div>
            <div class="modal-action">
                <button class="btn btn-primary" type="submit">Simpan</button>
                <button class="btn" onclick="edit_modal.close()" type="reset">Batal</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Modal -->
    <dialog id="delete_modal" class="modal">
        <form method="POST" class="modal-box">
            @csrf
            @method('DELETE')

            <input type="hidden" name="location_id" id="delete_location_id">

            <h3 class="text-lg font-bold mb-4">Hapus Lokasi</h3>
            <p>Apakah Anda yakin ingin menghapus lokasi ini?</p>
            <div class="modal-action">
                <button class="btn btn-primary" type="submit">Hapus</button>
                <button class="btn" onclick="delete_modal.close()" type="reset">Batal</button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(button) {
            const name = button.dataset.nama;
            const id = button.dataset.id;
            const form = document.querySelector('#edit_modal form');

            document.getElementById("edit_location_name").value = name;
            document.getElementById("edit_location_id").value = id;

             // Set action dengan parameter ID
            form.action = `{{ url('/admin/locations') }}/${id}`

            edit_modal.showModal();
        }

        function openDeleteModal(button) {
            const id = button.dataset.id;
            const form = document.querySelector('#delete_modal form');
            document.getElementById("delete_location_id").value = id;

            // Set action dengan parameter ID
            form.action = `{{ url('/admin/locations') }}/${id}`

            delete_modal.showModal();
        }
</script>


@endsection

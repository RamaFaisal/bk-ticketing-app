<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        $tanggalRules = ['required', 'date'];
        if ($this->isMethod('post')) {
            $tanggalRules[] = 'after:now';
        }

        return [
            'judul'         => ['required', 'string', 'max:255'],
            'deskripsi'     => ['required', 'string'],
            'lokasi_id'        => ['required', 'string', 'max:255'],
            'kategori_id'   => ['required', 'exists:kategoris,id'],
            'tanggal_waktu' => $tanggalRules,
            'gambar'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            'tikets'          => ['required', 'array', 'min:1'],
            'tikets.*.tipe'   => ['required', 'in:reguler,premium'],
            'tikets.*.harga'  => ['required', 'numeric', 'min:0'],
            'tikets.*.stok'   => ['required', 'integer', 'min:0'],
            'tikets.*.id'     => ['nullable', 'exists:tickets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'         => 'Judul event wajib diisi.',
            'judul.max'              => 'Judul event maksimal 255 karakter.',

            'deskripsi.required'     => 'Deskripsi event wajib diisi.',

            'lokasi_id.required'        => 'Lokasi event wajib diisi.',
            'lokasi_id.max'             => 'Lokasi event maksimal 255 karakter.',

            'kategori_id.required'   => 'Kategori wajib dipilih.',
            'kategori_id.exists'     => 'Kategori yang dipilih tidak valid.',

            'tanggal_waktu.required' => 'Tanggal & waktu event wajib diisi.',
            'tanggal_waktu.date'     => 'Format tanggal & waktu tidak valid.',
            'tanggal_waktu.after'    => 'Tanggal & waktu event harus setelah waktu sekarang.',

            'gambar.image'           => 'File yang diunggah harus berupa gambar.',
            'gambar.mimes'           => 'Gambar harus berformat jpg, jpeg, atau png.',
            'gambar.max'             => 'Ukuran gambar maksimal 2MB.',

            'tikets.required'        => 'Minimal harus ada 1 tiket.',
            'tikets.array'           => 'Format data tiket tidak valid.',
            'tikets.min'             => 'Minimal harus ada 1 tiket.',

            'tikets.*.tipe.required'  => 'Tipe tiket wajib dipilih.',
            'tikets.*.tipe.in'        => 'Tipe tiket harus reguler atau premium.',

            'tikets.*.harga.required' => 'Harga tiket wajib diisi.',
            'tikets.*.harga.numeric'  => 'Harga tiket harus berupa angka.',
            'tikets.*.harga.min'      => 'Harga tiket tidak boleh negatif.',

            'tikets.*.stok.required'  => 'Stok tiket wajib diisi.',
            'tikets.*.stok.integer'   => 'Stok tiket harus berupa bilangan bulat.',
            'tikets.*.stok.min'       => 'Stok tiket tidak boleh negatif.',

            'tikets.*.id.exists'      => 'Tiket yang direferensikan tidak ditemukan.',
        ];
    }
}

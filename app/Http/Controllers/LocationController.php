<?php

namespace App\Http\Controllers;

use App\Models\lokasi;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index() {
        $locations = lokasi::all()->where('aktif', 'Y');

        return view('pages.admin.lokasi.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lokasi' => 'required|string|max:255|unique:lokasis,nama_lokasi',
        ]);

        lokasi::create([
            'nama_lokasi' => $request->nama_lokasi,
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_lokasi' => 'required|string|max:255|unique:lokasis,nama_lokasi,' . $id,
        ]);

        $locations = lokasi::findOrFail($id);
        $locations->update([
            'nama_lokasi' => $request->nama_lokasi,
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $locations = lokasi::findOrFail($id);
        $locations->update([
            'aktif' => 'T',
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil dihapus!');
    }
}

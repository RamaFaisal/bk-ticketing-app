<?php

namespace Database\Seeders;

use App\Models\lokasi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'id' => 1,
                'nama_lokasi' => 'Stadion Utama',
                'aktif' => 'Y',
            ],
            [
                'id' => 2,
                'nama_lokasi' => 'Galeri Seni Kota',
                'aktif' => 'Y',
            ],[
                'id' => 3,
                'nama_lokasi' => 'Taman Kota',
                'aktif' => 'Y',
            ],
        ];
        foreach ($locations as $lokasi) {
            lokasi::create($lokasi);
        }
    }
}

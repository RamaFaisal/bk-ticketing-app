<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EventExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private ?string $kategori_id = null,
        private ?string $search = null,
    ) {}

    public function collection()
    {
        $query = Event::with(['kategori', 'tickets']);

        if ($this->kategori_id) {
            $query->where('kategori_id', $this->kategori_id);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('judul', 'like', "%{$this->search}%")
                ->orWhere('lokasi', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('tanggal_waktu')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Judul',
            'Kategori',
            'Lokasi',
            'Tanggal & Waktu',
            'Jumlah Tiket',
            'Status',
        ];
    }

    public function map($event): array
    {
        return [
            $event->id,
            $event->judul,
            $event->kategori->nama ?? 'N/A',
            $event->lokasi,
            $event->tanggal_waktu,
            $event->tickets->count(),
            $event->status,
        ];
    }
}

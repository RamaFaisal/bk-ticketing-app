<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'nama',
        'deskripsi',
        'tanggal',
        'lokasi',
        'gambar'
    ];

    protected $cast = [
        'tanggal' => 'datetime',
    ];

    public function tickets() {
        return $this->hasMany(Ticket::class);
    }

    public function kategori() {
        return $this->belongsTo(Kategori::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}

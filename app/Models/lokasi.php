<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class lokasi extends Model
{
    protected $fillable = [
        'nama_lokasi',
        'aktif',
    ];

    public function event() {
        return $this->hasMany(Event::class);
    }
}

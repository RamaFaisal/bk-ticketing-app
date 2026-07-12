<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'tanggal_waktu',
        'lokasi',
        'gambar',
        'user_id',
        'kategori_id'
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
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

    public function getStatusAttribute(): string
    {
        $now = now();

        if ($this->tanggal_waktu > $now) {
            return 'Upcoming';
        }
        
        if ($this->tanggal_waktu >= $now->copy()->subHours(3)) {
            return 'Ongoing';
        }

        return 'Completed';
    }

    public function hasSales(): bool
    {
        return $this->orders()->exists();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('tanggal_waktu', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->whereBetween('tanggal_waktu', [now()->subHours(3), now()]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('tanggal_waktu', '<', now()->subHours(3));
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->gambar && filter_var($this->gambar, FILTER_VALIDATE_URL)) {
            return $this->gambar;
        }

        if ($this->gambar && file_exists(public_path('storage/' . $this->gambar))) {
            return asset('storage/' . $this->gambar);
        }

        return asset('storage/konser.jpg');
    }
}

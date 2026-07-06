<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BukuPegangan extends Model
{
    use HasUuids;

    protected $fillable = [
        'plotting_id',
        'judul_buku',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'jenis_buku'
    ];

    // Relasi ke tabel plotting utama
    public function plotting()
    {
        return $this->belongsTo(Plotting::class, 'plotting_id');
    }
}

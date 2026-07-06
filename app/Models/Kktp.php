<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kktp extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tujuan_pembelajaran_id',
        'plotting_id', // 🟢 Ubah dari kelas_id menjadi plotting_id
        'target_nilai'
    ];

    // Tambahkan relasi ke Plotting
    public function plotting()
    {
        return $this->belongsTo(Plotting::class, 'plotting_id');
    }

    public function tujuanPembelajaran()
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}

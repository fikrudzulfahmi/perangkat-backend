<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Plotting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tahun_pelajaran_id',
        'guru_id',
        'mapel_id',
        'jp_per_minggu'
    ];

    // Relasi
    public function tahunPelajaran()
    {
        return $this->belongsTo(TahunPelajaran::class, 'tahun_pelajaran_id');
    }
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
    public function mapel()
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id'); // Sesuaikan dengan nama model Mapel Anda
    }


    public function listKelas()
    {
        return $this->belongsToMany(Kelas::class, 'plotting_kelas', 'plotting_id', 'kelas_id')
            ->using(\App\Models\PlottingKelas::class) // Gunakan model pivot kustom kita
            ->withTimestamps();
    }
}

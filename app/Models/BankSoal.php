<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BankSoal extends Model
{
    use HasUuids;

    protected $fillable = [
        'plotting_id',
        'tp_id',
        'jenis_asesmen',
        'tipe_soal',
        'tingkat_kesulitan',
        'bobot_nilai',
        'pertanyaan',
        'pilihan_jawaban',
        'kunci_jawaban'
    ];

    protected $casts = [
        'pilihan_jawaban' => 'array', // Otomatis handle JSON ke Array
        'bobot_nilai'     => 'integer',
    ];

    public function plotting()
    {
        return $this->belongsTo(Plotting::class, 'plotting_id');
    }

    // Pastikan model TujuanPembelajaran sudah ada sesuai strukturmu
    public function tujuanPembelajaran()
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_id');
    }
}

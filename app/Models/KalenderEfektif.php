<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KalenderEfektif extends Model
{
    use HasFactory, HasUuids; // 🟢 Menggunakan kombinasi Factory dan otomatisasi UUID

    protected $table = 'kalender_efektifs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tahun_pelajaran_id',
        'semester',
        'bulan',
        'jumlah_minggu',
        'minggu_efektif',
        'minggu_tidak_efektif',
        'keterangan',
        'file_pdf'
    ];

    // Relasi balik ke Tahun Pelajaran
    public function tahunPelajaran()
    {
        return $this->belongsTo(TahunPelajaran::class, 'tahun_pelajaran_id');
    }
}

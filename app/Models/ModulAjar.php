<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ModulAjar extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'modul_ajars';

    // Karena menggunakan UUID, beritahu Eloquent bahwa primary key-nya string
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'plotting_id',
        'bab_atau_materi',
        'pertemuan_ke',
        'alokasi_waktu',
        'profil_pancasila',
        'sarana_prasarana',
        'target_peserta',
        'model_pembelajaran',
        'pertanyaan_pemantik',
        'pemahaman_bermakna',
        'kegiatan_pembelajaran',
        'lkpd',
        'glosarium_pustaka',
        // TAMBAHKAN 5 BARIS INI:
        'asesmen_diagnostik',
        'asesmen_formatif',
        'asesmen_sumatif',
        'remedial_content',
        'enrichment_content'
    ];

    protected $casts = [
        'profil_pancasila'      => 'array',
        'kegiatan_pembelajaran' => 'array',
        // TAMBAHKAN CASTING INI:
        'asesmen_diagnostik'    => 'boolean',
        'asesmen_formatif'      => 'boolean',
        'asesmen_sumatif'       => 'boolean',
    ];

    /**
     * Relasi ke tabel Plotting (BelongsTo)
     */
    public function plotting()
    {
        return $this->belongsTo(Plotting::class, 'plotting_id');
    }

    /**
     * Relasi ke Tujuan Pembelajaran (Many-to-Many via tabel pivot)
     */
    public function tujuanPembelajarans()
    {
        return $this->belongsToMany(
            TujuanPembelajaran::class,
            'modul_ajar_tp',
            'modul_ajar_id',
            'tujuan_pembelajaran_id'
        )->withTimestamps();
    }

    /**
     * Relasi ke Bank Soal (Many-to-Many via tabel pivot)
     */
    public function bankSoals()
    {
        return $this->belongsToMany(
            BankSoal::class,
            'modul_ajar_soal',
            'modul_ajar_id',
            'bank_soal_id'
        )->withTimestamps();
    }
}

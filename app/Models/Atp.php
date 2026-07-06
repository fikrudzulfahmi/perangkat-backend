<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atp extends Model
{
    use HasFactory;

    // Tunjuk secara eksplisit nama tabelnya (karena Laravel defaultnya akan mencari tabel 'atps')
    protected $table = 'atp';

    protected $fillable = [
        'guru_id',
        'mapel_id',
        'plotting_id', // 🟢 GANTI 'kelas_id' menjadi 'plotting_id'
        'tujuan_pembelajaran_id',
        'semester',
        'nomor_urut',
        'alokasi_jp',
    ];

    // =======================================
    // DEFINISI RELASI
    // =======================================

    public function guru()
    {
        // Asumsi data guru menyatu di tabel 'users'
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function mapel()
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    // 🟢 UBAH RELASI KELAS MENJADI PLOTTING
    public function plotting()
    {
        // Hubungkan ke model Plotting (sesuaikan namespace model Plotting Anda jika berbeda)
        return $this->belongsTo(Plotting::class, 'plotting_id');
    }

    public function tujuanPembelajaran()
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}

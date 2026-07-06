<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CapaianPembelajaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'capaian_pembelajarans';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mapel_id',
        'fase',
        'elemen',
        'deskripsi'
    ];

    // Relasi ke Mata Pelajaran
    public function mapel()
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    // Relasi ke Tujuan Pembelajaran (Satu CP punya banyak TP)
    public function listTp()
    {
        return $this->hasMany(TujuanPembelajaran::class, 'capaian_pembelajaran_id');
    }
}

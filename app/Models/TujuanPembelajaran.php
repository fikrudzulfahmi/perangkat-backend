<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TujuanPembelajaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tujuan_pembelajarans';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'capaian_pembelajaran_id',
        'kode_tp',
        'deskripsi'
    ];

    // Relasi balik ke CP
    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TahunPelajaran extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nama_tahun',
        'is_active'
    ];

    // Otomatis ubah tipe data is_active menjadi boolean (true/false)
    protected $casts = [
        'is_active' => 'boolean',
    ];
}

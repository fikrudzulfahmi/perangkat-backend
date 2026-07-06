<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // <-- Impor Trait UUID

class MataPelajaran extends Model
{
    use HasFactory, HasUuids; // <-- Panggil HasUuids di sini

    protected $fillable = ['kode_mapel', 'nama_mapel'];
}

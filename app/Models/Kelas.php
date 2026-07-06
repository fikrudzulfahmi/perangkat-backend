<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Import Trait UUID

class Kelas extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kelas'; // Menegaskan nama tabel
    protected $fillable = ['nama_kelas'];
}

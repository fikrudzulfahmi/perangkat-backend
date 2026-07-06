<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenStatis extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dokumen_statis';

    protected $fillable = [
        'jenis_dokumen',
        'isi_dokumen'
    ];
}

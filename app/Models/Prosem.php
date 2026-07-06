<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prosem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'plotting_id',
        'tujuan_pembelajaran_id',
        'bulan',
        'minggu_ke',
        'alokasi_jp'
    ];
}

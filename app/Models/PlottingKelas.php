<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlottingKelas extends Pivot
{
    use HasUuids; // Mengotomatiskan pembuatan UUID pada kolom id tabel pivot

    protected $table = 'plotting_kelas';
}

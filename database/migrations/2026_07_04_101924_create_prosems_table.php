<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prosems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plotting_id'); // Relasi ke master plotting guru
            $table->uuid('tujuan_pembelajaran_id'); // Relasi ke Tujuan Pembelajaran (TP)
            $table->unsignedTinyInteger('bulan'); // Angka 1 - 12 (7-12 Ganjil, 1-6 Genap)
            $table->unsignedTinyInteger('minggu_ke'); // Minggu ke 1 - 5
            $table->unsignedInteger('alokasi_jp'); // Jumlah JP yang diisi guru di minggu tersebut
            $table->timestamps();

            // Constraint agar tidak ada duplikasi input untuk plotting, TP, bulan, & minggu yang sama
            $table->unique(['plotting_id', 'tujuan_pembelajaran_id', 'bulan', 'minggu_ke'], 'prosem_matrix_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prosems');
    }
};

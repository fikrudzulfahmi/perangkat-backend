<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tujuan_pembelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('capaian_pembelajaran_id'); // Relasi ke CP induknya
            $table->string('kode_tp', 20); // Contoh: TP 1.1, TP 1.2
            $table->text('deskripsi'); // Isi teks tujuan pembelajaran
            $table->timestamps();

            // Foreign key ke tabel capaian_pembelajarans
            $table->foreign('capaian_pembelajaran_id')
                ->references('id')
                ->on('capaian_pembelajarans')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tujuan_pembelajarans');
    }
};

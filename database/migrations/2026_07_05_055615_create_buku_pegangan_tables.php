<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buku_pegangans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi langsung ke tabel plotting utama (gambar pertama)
            $table->foreignUuid('plotting_id')->constrained('plottings')->cascadeOnDelete();

            // Detail Buku
            $table->string('judul_buku');
            $table->string('penulis')->nullable();
            $table->string('penerbit')->nullable();
            $table->year('tahun_terbit')->nullable();
            $table->enum('jenis_buku', ['Buku Guru', 'Buku Siswa', 'Referensi Lain']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buku_pegangan_tables');
    }
};

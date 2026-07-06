<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // 🟢 Menggunakan UUID sebagai Primary Key
            $table->string('nisn')->nullable();
            $table->string('nis')->nullable();
            $table->string('nama_siswa');

            // Relasi UUID ke tabel kelas dan tahun pelajaran (Pastikan foreignUuid sesuai tipe tabel induk)
            $table->foreignUuid('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->foreignUuid('tahun_pelajaran_id')->constrained('tahun_pelajarans')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};

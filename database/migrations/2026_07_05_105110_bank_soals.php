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
        Schema::create('bank_soals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi Utama
            $table->foreignUuid('plotting_id')->constrained('plottings')->cascadeOnDelete();
            $table->foreignUuid('tp_id')->nullable()->constrained('tujuan_pembelajarans')->nullOnDelete();

            // Kategori Soal
            $table->enum('jenis_asesmen', ['Formatif', 'Sumatif']);
            $table->enum('tipe_soal', ['Pilihan Ganda', 'Esai', 'Praktik/Unjuk Kerja']);

            // === TAMBAHAN BARU SESUAI DISKUSI ===
            $table->enum('tingkat_kesulitan', ['Mudah', 'Sedang', 'Sulit']); // Bisa diganti C1, C2, C3, dst jika sekolah pakai standar itu
            $table->integer('bobot_nilai')->default(10);
            // ====================================

            // Konten Soal
            $table->text('pertanyaan');
            $table->json('pilihan_jawaban')->nullable();
            $table->text('kunci_jawaban')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_soals');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_efektifs', function (Blueprint $table) {
            // Menggunakan UUID sebagai Primary Key sesuai standar Anda
            $table->uuid('id')->primary();

            $table->enum('semester', ['Ganjil', 'Genap']);
            // Relasi ke tahun pelajaran
            $table->uuid('tahun_pelajaran_id');

            // Data rincian per bulan
            $table->string('bulan', 20); // Januari, Februari, dst.
            $table->integer('jumlah_minggu')->default(0);
            $table->integer('minggu_efektif')->default(0);
            $table->integer('minggu_tidak_efektif')->default(0);
            $table->string('keterangan')->nullable(); // Alasan tidak efektif (misal: Libur Semester)

            // Kolom untuk menampung path file PDF Kalender Akademik fisik
            $table->string('file_pdf')->nullable();

            $table->timestamps();

            // Foreign key constraint ke tabel tahun_pelajarans
            $table->foreign('tahun_pelajaran_id')
                ->references('id')
                ->on('tahun_pelajarans')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_efektifs');
    }
};

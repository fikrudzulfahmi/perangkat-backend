<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom hasil ANALISIS CP (sesuai KSP).
     * CP resmi (deskripsi) dipertahankan; kolom baru ini adalah hasil
     * penguraian CP menjadi kompetensi, konten/materi, dan bentuk pemahaman.
     * Semua nullable agar data CP lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('capaian_pembelajarans', function (Blueprint $table) {
            $table->text('kompetensi')->nullable()->after('deskripsi');
            $table->text('konten_materi')->nullable()->after('kompetensi');
            $table->text('bentuk_pemahaman')->nullable()->after('konten_materi');
        });
    }

    public function down(): void
    {
        Schema::table('capaian_pembelajarans', function (Blueprint $table) {
            $table->dropColumn(['kompetensi', 'konten_materi', 'bentuk_pemahaman']);
        });
    }
};

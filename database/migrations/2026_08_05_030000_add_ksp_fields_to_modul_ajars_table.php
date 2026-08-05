<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom komponen Sistematika RPM sesuai Juknis KSP 2025.
     * Semua kolom nullable supaya data modul ajar lama tetap aman.
     * Kolom target_peserta TIDAK di-drop (dibiarkan) — digantikan identifikasi_murid.
     */
    public function up(): void
    {
        Schema::table('modul_ajars', function (Blueprint $table) {
            $table->text('identifikasi_murid')->nullable()->after('target_peserta');
            $table->text('analisis_materi')->nullable()->after('identifikasi_murid');
            $table->text('capaian_pembelajaran')->nullable()->after('analisis_materi');
            $table->text('kemitraan')->nullable()->after('capaian_pembelajaran');
            $table->text('lingkungan_belajar')->nullable()->after('kemitraan');
            $table->text('pemanfaatan_digital')->nullable()->after('lingkungan_belajar');
        });
    }

    public function down(): void
    {
        Schema::table('modul_ajars', function (Blueprint $table) {
            $table->dropColumn([
                'identifikasi_murid',
                'analisis_materi',
                'capaian_pembelajaran',
                'kemitraan',
                'lingkungan_belajar',
                'pemanfaatan_digital',
            ]);
        });
    }
};

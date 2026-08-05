<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom format ATP sesuai KSP:
     * DPL (Dimensi Profil Lulusan), Sumber Belajar, dan Asesmen.
     * Semua nullable agar data ATP lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('atp', function (Blueprint $table) {
            $table->string('dpl')->nullable()->after('alokasi_jp');
            $table->text('sumber_belajar')->nullable()->after('dpl');
            $table->string('asesmen')->nullable()->after('sumber_belajar');
        });
    }

    public function down(): void
    {
        Schema::table('atp', function (Blueprint $table) {
            $table->dropColumn(['dpl', 'sumber_belajar', 'asesmen']);
        });
    }
};

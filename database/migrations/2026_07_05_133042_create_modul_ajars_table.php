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
        // 1. Tabel Utama Modul Ajar
        Schema::create('modul_ajars', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Menggunakan UUID menyesuaikan ekosistem aplikasimu
            $table->foreignUuid('plotting_id')->constrained('plottings')->cascadeOnDelete();

            $table->string('bab_atau_materi');
            $table->string('pertemuan_ke');
            $table->string('alokasi_waktu');
            $table->json('profil_pancasila');
            $table->text('sarana_prasarana')->nullable();
            $table->string('target_peserta')->default('Reguler');
            $table->string('model_pembelajaran');
            $table->text('pertanyaan_pemantik')->nullable();
            $table->text('pemahaman_bermakna')->nullable();
            $table->json('kegiatan_pembelajaran'); // Berisi: tahap, durasi, dan aktivitas
            $table->text('lkpd')->nullable();
            $table->text('glosarium_pustaka')->nullable();

            $table->timestamps();
        });

        // 2. Tabel Pivot: Modul Ajar & Tujuan Pembelajaran (TP)
        Schema::create('modul_ajar_tp', function (Blueprint $table) {
            $table->id(); // PK tabel pivot boleh integer biasa
            $table->foreignUuid('modul_ajar_id')->constrained('modul_ajars')->cascadeOnDelete();
            $table->foreignUuid('tujuan_pembelajaran_id')->constrained('tujuan_pembelajarans')->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Tabel Pivot: Modul Ajar & Bank Soal
        Schema::create('modul_ajar_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('modul_ajar_id')->constrained('modul_ajars')->cascadeOnDelete();
            $table->foreignUuid('bank_soal_id')->constrained('bank_soals')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modul_ajar_soal');
        Schema::dropIfExists('modul_ajar_tp');
        Schema::dropIfExists('modul_ajars');
    }
};

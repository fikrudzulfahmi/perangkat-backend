<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atp', function (Blueprint $table) {
            // ID untuk tabel ATP sendiri (bisa biarkan auto increment, atau ganti $table->uuid('id')->primary() jika ATP juga pakai UUID)
            $table->id();

            $table->uuid('guru_id')->comment('Diambil dari tabel users');

            // 🟢 PERBAIKAN: Gunakan foreignUuid untuk semua relasi
            // Catatan: Kembalikan nama tabelnya tanpa 's' jika di error pertama Anda menggunakan mata_pelajaran
            $table->foreignUuid('mapel_id')->constrained('mata_pelajarans')->cascadeOnDelete();
            $table->foreignUuid('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignUuid('tujuan_pembelajaran_id')->constrained('tujuan_pembelajarans')->cascadeOnDelete();

            $table->string('semester');
            $table->integer('nomor_urut');
            $table->integer('alokasi_jp');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atp');
    }
};

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
        Schema::create('jadwal_mengajars', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tahun_pelajaran_id')->constrained('tahun_pelajarans')->onDelete('cascade');
            $table->foreignUuid('guru_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('mata_pelajaran_id')->constrained('mata_pelajarans')->onDelete('cascade');
            $table->foreignUuid('kelas_id')->constrained('kelas')->onDelete('cascade');

            // TAMBAHKAN KOLOM INI (sebagai penanda Blok)
            $table->string('blok'); // Misal isinya: "Blok A", "Blok B", "Blok 1", atau "Minggu 1-2"

            $table->string('hari');
            $table->string('jam_ke');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_mengajars');
    }
};

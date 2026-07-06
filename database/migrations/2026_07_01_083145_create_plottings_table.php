<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plottings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tahun_pelajaran_id');
            $table->uuid('guru_id'); // Ini merujuk ke tabel users (role guru)
            $table->uuid('mapel_id');
            $table->timestamps();

            // Opsional: Jika ingin ketat, tambahkan foreign key constraint
            // $table->foreign('tahun_pelajaran_id')->references('id')->on('tahun_pelajarans')->onDelete('cascade');
            // $table->foreign('guru_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('mapel_id')->references('id')->on('mata_pelajarans')->onDelete('cascade');
            // $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plottings');
    }
};

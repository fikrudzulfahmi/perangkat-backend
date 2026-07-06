<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mata_pelajarans', function (Blueprint $table) {
            // Menggunakan UUID sebagai Primary Key sesuai Blueprint
            $table->uuid('id')->primary();

            $table->string('kode_mapel')->unique(); // Contoh: MAPEL-01
            $table->string('nama_mapel');           // Contoh: Matematika
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};

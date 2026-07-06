<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_statis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Kolom jenis untuk membedakan ('kode_etik', 'ikrar_guru', 'tata_tertib')
            $table->string('jenis_dokumen')->unique();
            // Menggunakan longText karena dokumen bisa sangat panjang
            $table->longText('isi_dokumen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_statis');
    }
};

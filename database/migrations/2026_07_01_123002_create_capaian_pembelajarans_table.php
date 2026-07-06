<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capaian_pembelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // 🟢 PERBAIKAN: Diarahkan ke tabel 'mata_pelajarans' sesuai database Anda
            $table->foreignUuid('mapel_id')
                ->constrained('mata_pelajarans')
                ->onDelete('cascade');

            $table->string('fase', 2); // 'A', 'B', 'C', 'D', 'E', 'F'
            $table->string('elemen');
            $table->text('deskripsi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_pembelajarans');
    }
};

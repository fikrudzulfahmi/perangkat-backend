<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kktps', function (Blueprint $table) {
            // UUID sebagai Primary Key
            $table->uuid('id')->primary();

            // Foreign Key berbasis UUID
            $table->uuid('tujuan_pembelajaran_id');
            $table->uuid('kelas_id');

            // Nilai Target KKTP
            $table->integer('target_nilai')->default(75);
            $table->timestamps();

            // Relasi Constraint
            $table->foreign('tujuan_pembelajaran_id')->references('id')->on('tujuan_pembelajarans')->onDelete('cascade');
            $table->foreign('kelas_id')->references('id')->on('kelas')->onDelete('cascade');

            // Unique Composite Key agar tidak ada duplikasi data TP di kelas yang sama
            $table->unique(['tujuan_pembelajaran_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kktps');
    }
};

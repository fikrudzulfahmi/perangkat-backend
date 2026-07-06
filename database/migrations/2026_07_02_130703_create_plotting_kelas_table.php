<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plotting_kelas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Jika menggunakan trait HasUuids
            $table->foreignUuid('plotting_id')->constrained('plottings')->cascadeOnDelete();
            $table->foreignUuid('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plotting_kelas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 💡 Pastikan tambahkan import ini

return new class extends Migration
{
    public function up(): void
    {
        // 🛠️ 1. Bersihkan data dummy lama agar lolos pengecekan Foreign Key
        // Karena isi UUID kelas lama tidak akan nyambung dengan ID Ploting baru
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('atp')->truncate();
        DB::table('kktps')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Modifikasi Tabel ATP
        Schema::table('atp', function (Blueprint $table) {
            // $table->dropForeign(['kelas_id']);
            $table->renameColumn('kelas_id', 'plotting_id');
            // 💡 Sesuai log error Anda, tabel targetnya bernama 'plottings'
            $table->foreign('plotting_id')->references('id')->on('plottings')->cascadeOnDelete();
        });

        // 3. Modifikasi Tabel KKTP
        Schema::table('kktps', function (Blueprint $table) {
            // $table->dropForeign(['kelas_id']);
            $table->renameColumn('kelas_id', 'plotting_id');
            $table->foreign('plotting_id')->references('id')->on('plottings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('atp', function (Blueprint $table) {
            $table->dropForeign(['plotting_id']);
            $table->renameColumn('plotting_id', 'kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
        });

        Schema::table('kktps', function (Blueprint $table) {
            $table->dropForeign(['plotting_id']);
            $table->renameColumn('plotting_id', 'kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
        });
    }
};

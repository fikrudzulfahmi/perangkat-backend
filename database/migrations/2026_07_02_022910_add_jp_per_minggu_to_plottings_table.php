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
        // 🟢 GANTI 'plottings' dengan nama tabel asli Agan di database
        Schema::table('plottings', function (Blueprint $table) {
            // Menambahkan kolom jp_per_minggu. 
            // Default 0 agar data lama tidak error saat di-migrate.
            $table->integer('jp_per_minggu')->default(0)->after('mapel_id');
        });
    }

    public function down(): void
    {
        Schema::table('plottings', function (Blueprint $table) {
            $table->dropColumn('jp_per_minggu');
        });
    }
};

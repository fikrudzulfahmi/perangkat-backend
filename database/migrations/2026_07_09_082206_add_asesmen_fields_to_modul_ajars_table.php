<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('modul_ajars', function (Blueprint $table) {
            // Tambahkan kolom asesmen (tipe boolean untuk checkbox)
            $table->boolean('asesmen_diagnostik')->default(false)->after('glosarium_pustaka');
            $table->boolean('asesmen_formatif')->default(false)->after('asesmen_diagnostik');
            $table->boolean('asesmen_sumatif')->default(false)->after('asesmen_formatif');

            // Tambahkan kolom remedial & pengayaan (tipe text karena isinya panjang)
            $table->text('remedial_content')->nullable()->after('asesmen_sumatif');
            $table->text('enrichment_content')->nullable()->after('remedial_content');
        });
    }

    public function down()
    {
        Schema::table('modul_ajars', function (Blueprint $table) {
            // Hapus kolom jika di-rollback
            $table->dropColumn([
                'asesmen_diagnostik',
                'asesmen_formatif',
                'asesmen_sumatif',
                'remedial_content',
                'enrichment_content'
            ]);
        });
    }
};

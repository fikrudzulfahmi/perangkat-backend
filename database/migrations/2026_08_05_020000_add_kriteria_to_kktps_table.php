<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom KRITERIA KETERCAPAIAN pada KKTP (format KSP).
     * Kriteria adalah rumusan kualitatif ketercapaian per TP yang ditulis guru,
     * misalnya: "Peserta didik mampu mengidentifikasi gangguan dengan tepat".
     * Nullable agar data lama tetap valid.
     */
    public function up(): void
    {
        Schema::table('kktps', function (Blueprint $table) {
            $table->text('kriteria')->nullable()->after('target_nilai');
        });
    }

    public function down(): void
    {
        Schema::table('kktps', function (Blueprint $table) {
            $table->dropColumn('kriteria');
        });
    }
};

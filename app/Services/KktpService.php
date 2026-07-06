<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;
use App\Models\Kktp;
use Illuminate\Support\Facades\DB;

class KktpService
{
    /**
     * Mengambil struktur matriks CP-TP dan data KKTP yang sudah tersimpan
     */
    public function getKktpStructure(string $mapelId, string $plottingId): array
    {
        $listCP = CapaianPembelajaran::with(['listTp'])
            ->where('mapel_id', $mapelId)
            ->get();

        // 🟢 Ubah pencarian menggunakan plotting_id
        $savedKktp = Kktp::where('plotting_id', $plottingId)
            ->get()
            ->keyBy('tujuan_pembelajaran_id');

        return [
            'list_cp' => $listCP,
            'saved_kktp' => $savedKktp
        ];
    }

    /**
     * Memproses penyimpanan massal menggunakan updateOrCreate
     */
    public function saveKktpData(string $plottingId, array $items): void
    {
        DB::transaction(function () use ($plottingId, $items) {
            foreach ($items as $item) {
                Kktp::updateOrCreate(
                    [
                        'tujuan_pembelajaran_id' => $item['tujuan_pembelajaran_id'],
                        'plotting_id' => $plottingId // 🟢 Simpan ke kolom plotting_id
                    ],
                    [
                        'target_nilai' => $item['target_nilai']
                    ]
                );
            }
        });
    }
}

<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;
use App\Models\Prosem;
use App\Models\KalenderEfektif; // Pastikan Model KalenderEfektif di-import
use Illuminate\Support\Facades\DB;

class ProsemService
{
    /**
     * Mengambil data struktur Prota & Prosem berdasarkan plotting_id
     */
    public function getProsemStructure(string $plottingId): array
    {
        // 1. Ambil data master plotting
        $plotting = DB::table('plottings')
            ->where('id', $plottingId)
            ->first();

        if (!$plotting) {
            throw new \Exception("Data plotting tidak ditemukan.");
        }

        // 2. Hitung Total RME berdasarkan Kalender Efektif
        // Asumsi: tabel 'plottings' memiliki kolom 'tahun_pelajaran_id'
        // Jika nama kolomnya berbeda (misal: 'id_tahun'), silakan disesuaikan.
        $tahunId = $plotting->tahun_pelajaran_id;

        if (!$tahunId) {
            // Fallback jika tidak ada tahun_pelajaran_id di plotting
            // Anda bisa melempar exception atau menggunakan default (misal 36)
            $totalRme = 36;
        } else {
            // Menjumlahkan kolom 'minggu_efektif' dari KalenderEfektif sesuai route Anda
            $totalRme = KalenderEfektif::where('tahun_pelajaran_id', $tahunId)
                ->sum('minggu_efektif');
        }

        $jpPerMinggu = $plotting->jp_per_minggu ?? 0;
        $totalJpTahunan = $totalRme * $jpPerMinggu;

        // 3. Ambil Struktur CP dan TP berdasarkan Mapel dari plotting tersebut
        $listCP = CapaianPembelajaran::with(['listTp'])
            ->where('mapel_id', $plotting->mapel_id)
            ->get();

        // 4. Ambil data matriks prosem yang sudah pernah disimpan guru sebelumnya
        $savedProsem = Prosem::where('plotting_id', $plottingId)
            ->get();

        return [
            'meta_plotting' => $plotting,
            'total_rme' => $totalRme,
            'jp_per_minggu' => $jpPerMinggu,
            'total_jp_tahunan' => $totalJpTahunan,
            'list_cp' => $listCP,
            'saved_prosem' => $savedProsem
        ];
    }

    /**
     * Menyimpan atau meng-update data pengisian matriks Prosem secara massal
     */
    public function saveProsemData(string $plottingId, array $items): void
    {
        DB::transaction(function () use ($plottingId, $items) {
            foreach ($items as $item) {
                // Jika alokasi_jp diisi 0 atau kosong, kita hapus datanya dari database supaya bersih
                if (empty($item['alokasi_jp']) || $item['alokasi_jp'] == 0) {
                    Prosem::where([
                        'plotting_id' => $plottingId,
                        'tujuan_pembelajaran_id' => $item['tujuan_pembelajaran_id'],
                        'bulan' => $item['bulan'],
                        'minggu_ke' => $item['minggu_ke']
                    ])->delete();
                } else {
                    // Jika ada isinya, lakukan updateOrCreate
                    Prosem::updateOrCreate(
                        [
                            'plotting_id' => $plottingId,
                            'tujuan_pembelajaran_id' => $item['tujuan_pembelajaran_id'],
                            'bulan' => $item['bulan'],
                            'minggu_ke' => $item['minggu_ke']
                        ],
                        [
                            'alokasi_jp' => $item['alokasi_jp']
                        ]
                    );
                }
            }
        });
    }
}

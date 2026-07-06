<?php

namespace App\Services;

use App\Models\Atp;
use Illuminate\Support\Facades\DB;

class AtpGuruService
{
    /**
     * Mengambil data matriks ATP spesifik milik guru
     */
    public function getAtpByGuru($guruId, $mapelId, $plottingId)
    {
        // 🟢 KARENA PAKAI PLOTTING_ID:
        // Kita tidak perlu mencari kelas_id dari tabel plotting_kelas lagi.
        // Langsung saja cari ke tabel 'atp' berdasarkan plotting_id.
        return Atp::where('guru_id', $guruId)
            ->where('mapel_id', $mapelId)
            ->where('plotting_id', $plottingId) // Menggantikan kelas_id
            ->get();
    }

    /**
     * Eksekusi penyimpanan bulk insert / update massal
     */
    public function simpanMassalAtp($guruId, array $validatedData)
    {
        DB::beginTransaction();
        try {
            // ID Plotting dikirim dari frontend melalui request dengan key 'kelas_id'
            $plottingId = $validatedData['kelas_id'];

            // 🟢 KARENA PAKAI PLOTTING_ID:
            // Kita hapus pencarian pluck('kelas_id') dan foreach kelasIds.
            // Data langsung disimpan per baris item tujuan pembelajaran ke plotting_id tersebut.
            foreach ($validatedData['items'] as $item) {
                Atp::updateOrCreate(
                    [
                        'guru_id'                => $guruId,
                        'mapel_id'               => $validatedData['mapel_id'],
                        'plotting_id'            => $plottingId, // Menyimpan langsung ke kolom plotting_id
                        'tujuan_pembelajaran_id' => $item['tujuan_pembelajaran_id']
                    ],
                    [
                        'semester'   => $item['semester'],
                        'nomor_urut' => $item['nomor_urut'],
                        'alokasi_jp' => $item['alokasi_jp'],
                    ]
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

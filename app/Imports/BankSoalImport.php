<?php

namespace App\Imports;

use App\Models\BankSoal;
use App\Models\Plotting;
use App\Models\TujuanPembelajaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // 💡 Menggunakan fitur heading nama kolom

class BankSoalImport implements ToModel, WithHeadingRow
{
    protected $plottingId;

    public function __construct($plottingId)
    {
        $this->plottingId = $plottingId;
    }

    public function model(array $row)
    {
        // 1. Ambil teks pertanyaan (Laravel Excel merubah "Pertanyaan / Instruksi" menjadi "pertanyaan_instruksi")
        $pertanyaan = $row['pertanyaan_instruksi'] ?? $row['pertanyaan'] ?? '';
        $pertanyaan = trim($pertanyaan);

        // Jika baris kosong, lewati aman
        if ($pertanyaan === '') {
            return null;
        }

        // 2. 🛡️ PROTEKSI TIPE SOAL (Auto-correct "Essay" -> "Esai")
        $tipeInput = isset($row['tipe_soal']) ? strtolower(trim($row['tipe_soal'])) : '';

        if ($tipeInput === 'pilihan ganda' || isset($row['opsi_a'])) {
            $tipeSoal = 'Pilihan Ganda';
        } else {
            $tipeSoal = 'Esai'; // Jika guru ngetik Essay, essay, atau Esai, tetap aman masuk ke DB sebagai 'Esai'
        }

        // 3. Ambil Opsi Jawaban hanya jika tipenya Pilihan Ganda
        $pilihanJawaban = null;
        if ($tipeSoal === 'Pilihan Ganda') {
            $pilihanJawaban = [
                $row['opsi_a'] ?? '',
                $row['opsi_b'] ?? '',
                $row['opsi_c'] ?? '',
                $row['opsi_d'] ?? '',
                $row['opsi_e'] ?? ''
            ];
        }

        // 4. Deteksi Kode TP secara dinamis dari nama kolom header
        $tpId = null;
        $keyKodeTp = collect(array_keys($row))->first(function ($k) {
            return str_contains($k, 'kode_tp');
        });
        $kodeTp = $keyKodeTp ? trim($row[$keyKodeTp]) : '';

        if ($kodeTp !== '') {
            $plotting = Plotting::find($this->plottingId);
            if ($plotting) {
                $tp = TujuanPembelajaran::where('kode_tp', $kodeTp)
                    ->whereHas('capaianPembelajaran', function ($q) use ($plotting) {
                        $q->where('mapel_id', $plotting->mapel_id);
                    })->first();

                if ($tp) {
                    $tpId = $tp->id;
                }
            }
        }

        // 5. Ambil kunci jawaban
        $kunciJawaban = $row['kunci_jawaban'] ?? $row['kunci_jawaban_rubrik'] ?? null;

        return new BankSoal([
            'plotting_id'       => $this->plottingId,
            'tp_id'             => $tpId,
            'jenis_asesmen'     => $row['jenis_asesmen'] ?? 'Formatif',
            'tipe_soal'         => $tipeSoal,
            'tingkat_kesulitan' => $row['tingkat_kesulitan'] ?? 'Sedang',
            'bobot_nilai'       => isset($row['bobot_nilai']) ? (int) $row['bobot_nilai'] : ($tipeSoal === 'Pilihan Ganda' ? 5 : 20),
            'pertanyaan'        => $pertanyaan,
            'pilihan_jawaban'   => $pilihanJawaban,
            'kunci_jawaban'     => $kunciJawaban,
        ]);
    }
}

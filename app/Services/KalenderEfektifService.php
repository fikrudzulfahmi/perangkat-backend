<?php

namespace App\Services;

use App\Models\KalenderEfektif;
use Illuminate\Support\Facades\Storage;

class KalenderEfektifService
{
    public function simpanKalenderMassal(array $data)
    {
        $tahunPelajaranId = $data['tahun_pelajaran_id'];
        $pathFilePdf = null;

        // 1. Cek apakah ada file PDF yang di-upload
        if (isset($data['file_pdf']) && $data['file_pdf']->isValid()) {
            $pathFilePdf = $data['file_pdf']->store('kalender', 'public');

            $kalenderLama = KalenderEfektif::where('tahun_pelajaran_id', $tahunPelajaranId)
                ->whereNotNull('file_pdf')
                ->first();
            if ($kalenderLama && $kalenderLama->file_pdf) {
                Storage::disk('public')->delete($kalenderLama->file_pdf);
            }
        }

        // 🟢 Array untuk mendeteksi Semester Ganjil
        $bulanGanjil = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // 2. Looping data rincian bulanan yang dikirim dari Vue 3
        foreach ($data['rincian'] as $item) {

            // 🟢 Tentukan Semester otomatis berdasarkan nama bulan
            $semesterAktif = in_array($item['bulan'], $bulanGanjil) ? 'Ganjil' : 'Genap';

            // Siapkan payload data yang akan di-insert/update
            $payload = [
                'semester'             => $semesterAktif, // <--- INI TAMBAHANNYA
                'jumlah_minggu'        => $item['jumlah_minggu'],
                'minggu_efektif'       => $item['minggu_efektif'],
                'minggu_tidak_efektif' => $item['minggu_tidak_efektif'],
                'keterangan'           => $item['keterangan'] ?? null,
            ];

            if ($pathFilePdf) {
                $payload['file_pdf'] = $pathFilePdf;
            }

            KalenderEfektif::updateOrCreate(
                [
                    'tahun_pelajaran_id' => $tahunPelajaranId,
                    'bulan'              => $item['bulan']
                ],
                $payload
            );
        }

        return true;
    }
}

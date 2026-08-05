<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;
use App\Models\Prosem;
use App\Models\Atp;
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

        // 5. Ambil data ATP (semester, nomor_urut, alokasi_jp) untuk plotting ini,
        //    supaya halaman & cetak Prota/Prosem konsisten dengan urutan ATP.
        $savedAtp = Atp::where('plotting_id', $plottingId)
            ->get();

        // 6. Ambil kalender efektif per bulan (minggu efektif, keterangan libur, dll)
        //    untuk tahun pelajaran plotting ini. Dipakai:
        //    - Menghitung RME & target JP PER SEMESTER (bisa berbeda, mis. 19 vs 18 minggu)
        //    - Menampilkan kolom minggu dinamis di matriks Prosem (bulan yang
        //      tidak efektif tidak menampilkan kolom minggu / tidak bisa diisi)
        $bulanKeNomor = [
            'Juli' => 7, 'Agustus' => 8, 'September' => 9, 'Oktober' => 10,
            'November' => 11, 'Desember' => 12,
            'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
            'Mei' => 5, 'Juni' => 6,
        ];

        $kalenderBulanan = [];
        if ($tahunId) {
            $rows = KalenderEfektif::where('tahun_pelajaran_id', $tahunId)
                ->orderByRaw("FIELD(bulan, 'Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni')")
                ->get();

            foreach ($rows as $row) {
                $kalenderBulanan[] = [
                    'bulan' => $bulanKeNomor[$row->bulan] ?? null,
                    'nama_bulan' => $row->bulan,
                    'semester' => $row->semester,
                    'minggu_efektif' => (int) $row->minggu_efektif,
                    'minggu_tidak_efektif' => (int) $row->minggu_tidak_efektif,
                    'keterangan' => $row->keterangan,
                ];
            }
        }

        // RME & target JP per semester (fallback: kalau kolom semester kosong,
        // hitung dari bulan: 7-12 = Ganjil, 1-6 = Genap)
        $rmeSemester1 = 0;
        $rmeSemester2 = 0;
        foreach ($kalenderBulanan as $kb) {
            $isGanjil = in_array($kb['semester'], ['Ganjil', 'Semester 1', '1'])
                || ($kb['bulan'] !== null && $kb['bulan'] >= 7 && $kb['bulan'] <= 12);
            if ($isGanjil) {
                $rmeSemester1 += $kb['minggu_efektif'];
            } else {
                $rmeSemester2 += $kb['minggu_efektif'];
            }
        }

        // Fallback total RME jika kalender per bulan belum diisi (agar angka lama tetap jalan)
        if ($rmeSemester1 + $rmeSemester2 === 0) {
            $rmeSemester1 = (int) round($totalRme / 2);
            $rmeSemester2 = $totalRme - $rmeSemester1;
        }

        return [
            'meta_plotting' => $plotting,
            'total_rme' => $totalRme,
            'jp_per_minggu' => $jpPerMinggu,
            'total_jp_tahunan' => $totalJpTahunan,
            // Target per semester (semester 1 bisa beda dengan semester 2)
            'total_rme_semester_1' => $rmeSemester1,
            'total_rme_semester_2' => $rmeSemester2,
            'target_jp_semester_1' => $rmeSemester1 * $jpPerMinggu,
            'target_jp_semester_2' => $rmeSemester2 * $jpPerMinggu,
            // Kalender efektif per bulan untuk kolom minggu dinamis
            'kalender_bulanan' => $kalenderBulanan,
            'list_cp' => $listCP,
            'saved_prosem' => $savedProsem,
            'saved_atp' => $savedAtp
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

<?php

namespace App\Services;

use App\Models\Plotting;
use App\Models\Prosem;
use App\Models\TujuanPembelajaran; // sesuaikan namespace model TP Anda

class ProsemPlannerService
{
    private const MENIT_PER_JP = 45;

    // Proporsi default pembagian waktu dalam satu sesi pertemuan.
    // Sesuaikan angka ini kalau sekolah Anda punya standar proporsi berbeda.
    private const PROPORSI_PENDAHULUAN = 0.15;
    private const PROPORSI_INTI = 0.70;
    private const PROPORSI_PENUTUP = 0.15;

    /**
     * Menyusun rencana pembagian pertemuan per TP + alokasi waktu per bagian kegiatan,
     * berdasarkan data Plotting (jp_per_minggu) dan Prosem (alokasi_jp per TP per minggu).
     *
     * @param string $plottingId
     * @param array  $tujuanPembelajaranIds Daftar ID TP yang termasuk dalam modul ajar ini
     */
    public function buildRencanaPertemuan(string $plottingId, array $tujuanPembelajaranIds): array
    {
        $plotting = Plotting::findOrFail($plottingId);

        // 1. JP per pertemuan diambil langsung dari plotting (bukan parsing string lagi)
        $jpPerPertemuan = (int) $plotting->jp_per_minggu;
        $jpPerPertemuan = max($jpPerPertemuan, 1); // guard

        $totalMenitPerPertemuan = $jpPerPertemuan * self::MENIT_PER_JP;

        // 2. Bagi total menit per pertemuan ke pendahuluan / inti / penutup
        $menitPendahuluan = (int) round($totalMenitPerPertemuan * self::PROPORSI_PENDAHULUAN);
        $menitPenutup = (int) round($totalMenitPerPertemuan * self::PROPORSI_PENUTUP);
        // Sisa dialokasikan ke inti, biar totalnya pas (menghindari selisih pembulatan)
        $menitInti = $totalMenitPerPertemuan - $menitPendahuluan - $menitPenutup;

        // 3. Ambil baris Prosem untuk TP-TP yang dipilih, urut kronologis (bulan lalu minggu)
        $rows = Prosem::where('plotting_id', $plottingId)
            ->whereIn('tujuan_pembelajaran_id', $tujuanPembelajaranIds)
            ->orderBy('bulan')
            ->orderBy('minggu_ke')
            ->get()
            ->groupBy('tujuan_pembelajaran_id');

        // 4. Susun urutan TP sesuai kemunculan pertamanya di Prosem, plus total JP tiap TP
        $tpSequence = [];
        foreach ($rows as $tpId => $entries) {
            $tpSequence[] = [
                'tujuan_pembelajaran_id' => $tpId,
                'total_jp' => (int) $entries->sum('alokasi_jp'),
                'first_bulan' => (int) $entries->min('bulan'),
                'first_minggu' => (int) $entries->min('minggu_ke'),
            ];
        }

        usort($tpSequence, function ($a, $b) {
            return [$a['first_bulan'], $a['first_minggu']] <=> [$b['first_bulan'], $b['first_minggu']];
        });

        // 5. Konversi total JP tiap TP -> jumlah pertemuan (pembulatan ke atas),
        //    lalu tentukan rentang pertemuan kumulatif per TP
        $rencana = [];
        $pertemuanCursor = 1;

        foreach ($tpSequence as $tp) {
            $jumlahPertemuan = (int) ceil($tp['total_jp'] / $jpPerPertemuan);
            $jumlahPertemuan = max($jumlahPertemuan, 1);

            $mulai = $pertemuanCursor;
            $selesai = $pertemuanCursor + $jumlahPertemuan - 1;

            // Nama kolom disesuaikan dengan skema tabel TP Anda: kode_tp & deskripsi
            $tujuanPembelajaran = TujuanPembelajaran::find($tp['tujuan_pembelajaran_id']);

            $rencana[] = [
                'pertemuan_mulai' => $mulai,
                'pertemuan_selesai' => $selesai,
                'kode_tp' => $tujuanPembelajaran?->kode_tp ?? '-',
                'deskripsi_tp' => $tujuanPembelajaran?->deskripsi ?? '-',
                'total_jp' => $tp['total_jp'],
            ];

            $pertemuanCursor = $selesai + 1;
        }

        $totalPertemuan = empty($rencana) ? 0 : end($rencana)['pertemuan_selesai'];

        return [
            'rencana' => $rencana,
            'total_pertemuan' => $totalPertemuan,
            'jp_per_pertemuan' => $jpPerPertemuan,
            'total_menit_per_pertemuan' => $totalMenitPerPertemuan,
            'menit_pendahuluan' => $menitPendahuluan,
            'menit_inti' => $menitInti,
            'menit_penutup' => $menitPenutup,
        ];
    }

    /**
     * Ubah daftar rencana pertemuan jadi string siap di-inject ke prompt.
     */
    public function formatRencanaUntukPrompt(array $rencana): string
    {
        $lines = [];

        foreach ($rencana as $r) {
            $label = $r['pertemuan_mulai'] === $r['pertemuan_selesai']
                ? "Pertemuan {$r['pertemuan_mulai']}"
                : "Pertemuan {$r['pertemuan_mulai']}-{$r['pertemuan_selesai']}";

            $lines[] = "{$label} [Kode TP: {$r['kode_tp']}]: {$r['deskripsi_tp']} (Total {$r['total_jp']} JP)";
        }

        return implode("\n", $lines);
    }
}

/*
|--------------------------------------------------------------------------
| Contoh pemakaian di controller Anda
|--------------------------------------------------------------------------
|
| $planner = new \App\Services\ProsemPlannerService();
| // atau inject via constructor: public function __construct(private ProsemPlannerService $planner) {}
|
| $hasil = $planner->buildRencanaPertemuan(
|     $request->plotting_id,
|     $tujuanPembelajaranIds // array ID TP yang dipilih untuk modul ajar ini
| );
|
| $stringRencana = $planner->formatRencanaUntukPrompt($hasil['rencana']);
| $pertemuan = $hasil['total_pertemuan'];
| $waktuPendahuluan = $hasil['menit_pendahuluan'];
| $waktuInti = $hasil['menit_inti'];
| $waktuPenutup = $hasil['menit_penutup'];
| $waktuTotalPerSesi = $hasil['total_menit_per_pertemuan'];
|
| // lalu semua variabel ini tinggal disuntikkan ke $promptText
|
*/
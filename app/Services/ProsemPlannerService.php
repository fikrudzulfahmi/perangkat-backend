<?php

namespace App\Services;

use App\Models\Plotting;
use App\Models\Prosem;
use App\Models\TujuanPembelajaran; // sesuaikan namespace model TP Anda
use App\Models\KalenderEfektif;

class ProsemPlannerService
{
    private const MENIT_PER_JP = 45;

    // Proporsi default pembagian waktu dalam satu sesi pertemuan.
    // Sesuaikan angka ini kalau sekolah Anda punya standar proporsi berbeda.
    private const PROPORSI_PENDAHULUAN = 0.15;
    private const PROPORSI_INTI = 0.70;
    private const PROPORSI_PENUTUP = 0.15;

    // Bulan kalender (1-12) tempat TAHUN AJARAN dimulai. Standar Indonesia: Juli.
    // PENTING: kolom 'bulan' di Prosem cuma angka bulan kalender MENTAH (1-12),
    // tanpa info tahun/semester. Satu Plotting bisa mencakup SATU TAHUN AJARAN PENUH
    // (Juli tahun X s/d Juni tahun X+1). Kalau diurutkan langsung sebagai angka biasa,
    // Januari (1) akan dianggap lebih awal dari Juli (7) -- padahal Juli itu awal tahun
    // ajaran dan Januari sudah masuk semester genap (lebih akhir). Sesuaikan angka ini
    // kalau tahun ajaran di sekolah Anda mulai bulan lain.
    private const BULAN_AWAL_TAHUN_AJARAN = 7;

    /**
     * Ubah nomor bulan kalender (1-12) jadi "urutan bulan dalam tahun ajaran",
     * supaya bulan awal tahun ajaran selalu dianggap paling awal (urutan 0),
     * lalu berputar (wrap-around) sampai urutan 11 di bulan sebelum tahun ajaran
     * berikutnya dimulai. Contoh (awal=Juli): Juli=0, Agustus=1, ..., Desember=5,
     * Januari=6, ..., Juni=11.
     */
    private function urutanBulanTahunAjaran(int $bulan): int
    {
        return ($bulan - self::BULAN_AWAL_TAHUN_AJARAN + 12) % 12;
    }

    /**
     * Menyusun rencana pembagian pertemuan per TP + alokasi waktu per bagian kegiatan,
     * berdasarkan data Plotting (jp_per_minggu) dan Prosem (alokasi_jp per TP per minggu).
     *
     * PENTING soal penomoran pertemuan: nomor pertemuan dihitung dari SELURUH TP
     * dalam satu semester (satu $plottingId), berurutan sesuai kronologis Prosem
     * (bulan lalu minggu_ke) -- bukan hanya dari $tujuanPembelajaranIds yang dikirim.
     * Ini supaya saat guru membuat modul ajar baru untuk TP/elemen berikutnya dalam
     * semester yang sama, penomoran pertemuan MELANJUTKAN dari modul ajar sebelumnya
     * (mis. "Pertemuan 7-9"), bukan mulai dari 1 lagi. $tujuanPembelajaranIds hanya
     * dipakai untuk MENYARING baris mana saja yang ditampilkan di hasil akhir.
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

        // 3. Ambil baris Prosem SATU TAHUN AJARAN PENUH (semua TP di plotting ini),
        //    urut kronologis (bulan lalu minggu).
        //    TIDAK difilter ke $tujuanPembelajaranIds di sini. Setiap BARIS prosem
        //    (bulan + minggu_ke) = SATU PERTEMUAN, jadi jumlah pertemuan per TP
        //    dihitung dari jumlah baris/minggu tempat TP itu dijadwalkan guru.
        //
        //    PENTING: baris yang berada di minggu TIDAK EFEKTIF (libur/MPLS/ujian,
        //    sesuai tabel kalender_efektifs) ikut DIBUANG, konsisten dengan tampilan
        //    Prosem UI yang hanya menampilkan minggu efektif. Tanpa filter ini,
        //    posisi & total pertemuan tidak cocok dengan yang guru lihat di Prosem.
        $kalender = KalenderEfektif::where('tahun_pelajaran_id', $plotting->tahun_pelajaran_id)
            ->get()
            ->keyBy('bulan'); // key = nama bulan ('Juli')
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $rows = Prosem::where('plotting_id', $plottingId)->get();
        if ($kalender->isNotEmpty()) {
            $rows = $rows->filter(function ($r) use ($kalender, $namaBulan) {
                $k = $kalender->get($namaBulan[(int) $r->bulan] ?? '');
                if (!$k) {
                    return true; // bulan tanpa entri kalender -> biarkan (data lama)
                }
                return (int) $r->minggu_ke <= (int) $k->minggu_efektif;
            })->values();
        }
        $rows = $rows->groupBy('tujuan_pembelajaran_id');

        // 4. Susun urutan SEMUA TP di tahun ajaran ini sesuai kemunculan pertamanya
        //    di Prosem, plus jumlah minggu (pertemuan) tiap TP.
        //    Kemunculan pertama dicari dari BARIS ASLI (bukan ->min('bulan')
        //    dan ->min('minggu_ke') dipisah) supaya kombinasi bulan+minggu yang dipakai
        //    untuk urutan benar-benar pernah terjadi di data -- lalu bulan-nya dikonversi
        //    dulu ke urutanBulanTahunAjaran() supaya Juli-Desember selalu dianggap lebih
        //    awal dari Januari-Juni (lihat BULAN_AWAL_TAHUN_AJARAN di atas).
        $tpSequence = [];
        foreach ($rows as $tpId => $entries) {
            $entriPalingAwal = $entries->sortBy(function ($entry) {
                return $this->urutanBulanTahunAjaran((int) $entry->bulan) * 100 + (int) $entry->minggu_ke;
            })->first();

            $tpSequence[] = [
                'tujuan_pembelajaran_id' => $tpId,
                'jumlah_minggu' => (int) $entries->count(), // SATU baris prosem = SATU pertemuan
                'urutan_pertama' => $this->urutanBulanTahunAjaran((int) $entriPalingAwal->bulan) * 100
                    + (int) $entriPalingAwal->minggu_ke,
            ];
        }

        usort($tpSequence, fn($a, $b) => $a['urutan_pertama'] <=> $b['urutan_pertama']);

        // 5. Tentukan rentang pertemuan kumulatif untuk SEMUA TP di tahun ajaran ini
        //    (bukan hanya TP yang dipilih) sebagai "kalender pertemuan" lengkap.
        $rencanaSemua = [];
        $pertemuanCursor = 1;

        foreach ($tpSequence as $tp) {
            // Jumlah pertemuan = jumlah minggu TP muncul di Prosem (bukan
            // ceil(total_JP / JP per pertemuan), supaya persis sama dengan yang
            // guru jadwalkan di matriks Prosem).
            $jumlahPertemuan = max((int) $tp['jumlah_minggu'], 1);

            $mulai = $pertemuanCursor;
            $selesai = $pertemuanCursor + $jumlahPertemuan - 1;

            // Nama kolom disesuaikan dengan skema tabel TP Anda: kode_tp & deskripsi
            $tujuanPembelajaran = TujuanPembelajaran::find($tp['tujuan_pembelajaran_id']);

            $rencanaSemua[] = [
                'tujuan_pembelajaran_id' => $tp['tujuan_pembelajaran_id'],
                'pertemuan_mulai' => $mulai,
                'pertemuan_selesai' => $selesai,
                'kode_tp' => $tujuanPembelajaran?->kode_tp ?? '-',
                'deskripsi_tp' => $tujuanPembelajaran?->deskripsi ?? '-',
                'total_jp' => (int) $rows[$tp['tujuan_pembelajaran_id']]->sum('alokasi_jp'),
            ];

            $pertemuanCursor = $selesai + 1;
        }

        // 6. Baru sekarang saring ke TP yang benar-benar dipilih untuk modul ajar ini,
        //    lalu HITUNG ULANG penomoran pertemuan lokal mulai dari 1 (urutan sesuai
        //    kemunculan di Prosem). Modul ajar adalah dokumen yang berdiri sendiri,
        //    jadi pertemuan di dalamnya dihitung dari 1 -- bukan melanjutkan nomor
        //    global tahun ajaran (mis. "Pertemuan 12-20" yang membingungkan guru).
        $rencana = array_values(array_filter(
            $rencanaSemua,
            fn(array $r) => in_array($r['tujuan_pembelajaran_id'], $tujuanPembelajaranIds, true)
        ));

        // Simpan posisi GLOBAL di Prosem (nomor pertemuan sebenarnya dalam tahun
        // ajaran) SEBELUM penomoran lokal menimpanya -- dipakai frontend untuk
        // field "Pertemuan" yang menyesuaikan Prosem (mis. "12-20").
        foreach ($rencana as &$rEntry) {
            $rEntry['pertemuan_mulai_global'] = $rEntry['pertemuan_mulai'];
            $rEntry['pertemuan_selesai_global'] = $rEntry['pertemuan_selesai'];
        }
        unset($rEntry);

        $pertemuanAwalGlobal = empty($rencana) ? 0 : $rencana[0]['pertemuan_mulai'];
        $pertemuanAkhirGlobal = empty($rencana) ? 0 : end($rencana)['pertemuan_selesai'];
        $pertemuanLabelGlobal = $pertemuanAwalGlobal === $pertemuanAkhirGlobal
            ? (string) $pertemuanAwalGlobal
            : "{$pertemuanAwalGlobal}-{$pertemuanAkhirGlobal}";

        $pertemuanCursor = 1;
        foreach ($rencana as &$r) {
            $jumlah = $r['pertemuan_selesai'] - $r['pertemuan_mulai'] + 1;
            $r['pertemuan_mulai'] = $pertemuanCursor;
            $r['pertemuan_selesai'] = $pertemuanCursor + $jumlah - 1;
            $pertemuanCursor = $r['pertemuan_selesai'] + 1;
        }
        unset($r);

        // total_pertemuan di sini = jumlah SESI yang dicakup modul ajar ini saja
        // (lebar tiap rentang dijumlahkan).
        $totalPertemuan = array_reduce(
            $rencana,
            fn(int $carry, array $r) => $carry + ($r['pertemuan_selesai'] - $r['pertemuan_mulai'] + 1),
            0
        );

        // pertemuan_awal/pertemuan_akhir = nomor pertemuan dalam modul ajar ini
        // (mulai 1). $rencana sudah terurut sesuai kemunculan di Prosem.
        $pertemuanAwal = empty($rencana) ? 0 : $rencana[0]['pertemuan_mulai'];
        $pertemuanAkhir = empty($rencana) ? 0 : end($rencana)['pertemuan_selesai'];

        return [
            'rencana' => $rencana,
            'total_pertemuan' => $totalPertemuan,
            'pertemuan_awal' => $pertemuanAwal,
            'pertemuan_akhir' => $pertemuanAkhir,
            // Posisi global di Prosem (tahun ajaran penuh), untuk field "Pertemuan".
            'pertemuan_awal_global' => $pertemuanAwalGlobal,
            'pertemuan_akhir_global' => $pertemuanAkhirGlobal,
            'pertemuan_label_global' => $pertemuanLabelGlobal,
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
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TujuanPembelajaran; // sesuaikan namespace model TP Anda
use App\Services\ProsemPlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Controller ini menjembatani form Modul Ajar dengan Google Gemini API.
 *
 * PENTING: API key TIDAK PERNAH dikirim ke browser. Frontend (Vue) hanya
 * memanggil endpoint ini, lalu server yang memanggil Gemini menggunakan
 * key dari .env. Ini mencegah key bocor / dicuri dari kode frontend.
 *
 * PERUBAHAN: pertemuan_ke dan alokasi_waktu TIDAK LAGI diinput manual oleh guru.
 * Keduanya sekarang dihitung otomatis dari data Prosem (pembagian JP per TP per
 * minggu) dan Plotting (jp_per_minggu), lewat ProsemPlannerService.
 *
 * Ada 2 endpoint yang BERBAGI logika pembangunan prompt yang sama persis
 * (lewat buildPromptPayload), supaya prompt manual (preview) dan prompt yang
 * benar-benar dikirim ke Gemini TIDAK PERNAH berbeda/nyimpang satu sama lain:
 * - generateModul()  -> kirim prompt ke Gemini, kembalikan hasil isian form
 * - previewPrompt()  -> HANYA kembalikan teks prompt mentah, untuk tombol
 *                       "Salin Prompt Manual" di frontend (copy-paste ke AI lain)
 *
 * Cara pakai:
 * 1. Daftar & ambil API key gratis di https://aistudio.google.com
 * 2. Tambahkan di .env:  GEMINI_API_KEY=isi_key_anda_disini
 * 3. Daftarkan route (lihat contoh di routes_example.php)
 * 4. Frontend WAJIB mengirim 'plotting_id' dan 'tujuan_pembelajaran_id' (array UUID TP),
 *    BUKAN lagi 'pertemuan_ke' / 'alokasi_waktu' / 'tujuan_pembelajaran' (teks bebas).
 */
class AiAssistantController extends Controller
{
    public function __construct(private ProsemPlannerService $planner) {}

    /**
     * Endpoint utama: bangun prompt, kirim ke Gemini, kembalikan hasil JSON terstruktur.
     */
    public function generateModul(Request $request)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'message' => 'GEMINI_API_KEY belum diatur di server (.env). Hubungi admin aplikasi.'
            ], 500);
        }

        $built = $this->buildPromptPayload($request);
        if ($built instanceof JsonResponse) {
            return $built; // error validasi / Prosem kosong, sudah diformat jadi response
        }

        ['promptText' => $promptText, 'schema' => $schema, 'meta' => $meta] = $built;

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $promptText]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => $schema,
                    ],
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini API error: ' . $response->body());

                // Sementara ditampilkan apa adanya biar gampang di-debug.
                // Kalau sudah beres, boleh dikembalikan ke pesan generik supaya
                // detail teknis tidak terlihat oleh pengguna akhir.
                $pesanGoogle = data_get($response->json(), 'error.message', $response->body());
                return response()->json([
                    'message' => 'Gagal menghubungi layanan AI: ' . $pesanGoogle
                ], 502);
            }

            $rawText = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (!$rawText) {
                Log::error('Gemini response tidak berisi teks: ' . $response->body());
                return response()->json([
                    'message' => 'Respons AI kosong atau tidak sesuai format yang diharapkan.'
                ], 502);
            }

            $hasil = json_decode($rawText, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gagal parse JSON dari Gemini: ' . $rawText);
                return response()->json([
                    'message' => 'Gagal membaca hasil dari AI. Coba lagi.'
                ], 502);
            }

            // Gabungkan hasil AI menjadi struktur PER PERTEMUAN: pendahuluan + inti
            // + penutup, dicocokkan dengan rencana pertemuan dari Prosem (label
            // pertemuan & kode TP pasti dari database, bukan dari AI).
            $hasil['kegiatan_per_pertemuan'] = $this->buildKegiatanPerPertemuan(
                $built['rencana'],
                $hasil['kegiatan_inti'] ?? [],
                $hasil['kegiatan_pendahuluan'] ?? [],
                $hasil['kegiatan_penutup'] ?? []
            );

            // DPL & Asesmen mengikuti data yang SUDAH diisi guru di ATP (rantai KSP:
            // CP -> Analisis CP -> TP -> ATP). Kalau TP terpilih belum punya ATP,
            // array ini kosong dan frontend akan memakai rekomendasi AI.
            [$dplRekomendasi, $asesmenRekomendasi] = $this->sinkronDplAsesmenDariAtp(
                $validated['tujuan_pembelajaran_id'] ?? []
            );
            $hasil['dpl_rekomendasi'] = $dplRekomendasi;
            $hasil['asesmen_rekomendasi'] = $asesmenRekomendasi;

            // Sisipkan info rencana pertemuan ke response, berguna untuk frontend
            // auto-isi field Pertemuan Ke- / Alokasi Waktu / durasi tiap tahap.
            $hasil['_meta'] = $meta;

            return response()->json($hasil);
        } catch (\Exception $e) {
            Log::error('Gemini request exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghubungi layanan AI.'
            ], 500);
        }
    }

    /**
     * Endpoint preview: kembalikan teks prompt MENTAH (tanpa memanggil Gemini),
     * dipakai tombol "Salin Prompt Manual" supaya isinya identik dengan yang
     * benar-benar dipakai generateModul(), termasuk data Prosem/BBM/3M-nya.
     */
    public function previewPrompt(Request $request)
    {
        $built = $this->buildPromptPayload($request);
        if ($built instanceof JsonResponse) {
            return $built;
        }

        return response()->json([
            'prompt_text' => $built['promptText'],
            'meta'        => $built['meta'],
        ]);
    }

    // Analisis CP otomatis: uraikan deskripsi CP menjadi kompetensi,
    // konten/materi, dan bentuk pemahaman (KSP) via AI -- dipakai tombol
    // "Generate Analisis CP" di form CP (KurikulumGuruView).
    public function analisisCp(Request $request)
    {
        $request->validate([
            'elemen' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'message' => 'GEMINI_API_KEY belum diatur di server (.env). Hubungi admin aplikasi.'
            ], 500);
        }

        $schema = [
            'type' => 'object',
            'properties' => [
                'kompetensi' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Kompetensi/kemampuan yang dikembangkan, SATU butir per elemen array',
                ],
                'konten_materi' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Konten/materi pokok yang dipelajari, SATU butir per elemen array',
                ],
                'bentuk_pemahaman' => [
                    'type' => 'string',
                    'description' => 'Kalimat ringkas yang menggambarkan pemahaman akhir peserta didik dari CP ini',
                ],
            ],
            'required' => ['kompetensi', 'konten_materi', 'bentuk_pemahaman'],
        ];

        $promptText = <<<PROMPT
Uraikan Capaian Pembelajaran (CP) berikut menjadi tiga komponen Analisis CP sesuai pendekatan KSP (Kurikulum Satuan Pendidikan / Kurikulum Deep Learning Kemendikdasmen):

Elemen: {$request->elemen}
Deskripsi CP: {$request->deskripsi}

Aturan penguraian:
1. kompetensi — kemampuan yang harus dikuasai peserta didik, berupa kata kerja operasional (mengidentifikasi, menganalisis, mengevaluasi, dst). Tulis 3-6 butir, SATU butir per elemen array, ringkas (maksimal ±15 kata per butir), jangan memakai format penomoran.
2. konten_materi — materi pokok yang dipelajari untuk mencapai kompetensi tersebut. Tulis 3-6 butir, SATU butir per elemen array, ringkas.
3. bentuk_pemahaman — SATU kalimat padat yang menggambarkan pemahaman utuh peserta didik setelah menguasai CP (bukan sekadar mengulang deskripsi CP).

Jawab hanya JSON sesuai skema yang diberikan.
PROMPT;

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $promptText]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => $schema,
                    ],
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini analisis CP error: ' . $response->body());
                $pesanGoogle = data_get($response->json(), 'error.message', $response->body());
                return response()->json([
                    'message' => 'Gagal menghubungi layanan AI: ' . $pesanGoogle
                ], 502);
            }

            $rawText = data_get($response->json(), 'candidates.0.content.parts.0.text');
            if (!$rawText) {
                Log::error('Gemini analisis CP response tidak berisi teks: ' . $response->body());
                return response()->json([
                    'message' => 'Respons AI tidak berisi hasil analisis. Coba lagi.'
                ], 502);
            }

            $parsed = json_decode($rawText, true);
            if (!is_array($parsed)) {
                // Kalau AI membungkus JSON dalam markdown/teks lain, coba ekstrak blok JSON
                if (preg_match('/\{.*\}/s', $rawText, $m)) {
                    $parsed = json_decode($m[0], true);
                }
            }
            if (!is_array($parsed)) {
                Log::error('Gemini analisis CP JSON tidak valid: ' . $rawText);
                return response()->json([
                    'message' => 'Hasil AI tidak dapat dipahami. Silakan coba lagi.'
                ], 502);
            }

            $ambilBaris = fn($v) => is_array($v)
                ? implode("\n", array_values(array_filter(array_map('trim', $v))))
                : trim((string) $v);

            return response()->json([
                'data' => [
                    'kompetensi' => $ambilBaris($parsed['kompetensi'] ?? ''),
                    'konten_materi' => $ambilBaris($parsed['konten_materi'] ?? ''),
                    'bentuk_pemahaman' => $ambilBaris($parsed['bentuk_pemahaman'] ?? ''),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gemini analisis CP exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghubungi layanan AI.'
            ], 500);
        }
    }

    /**
     * Logika inti pembangunan prompt + schema, dipakai bareng oleh generateModul()
     * dan previewPrompt() supaya keduanya SELALU sinkron.
     *
     * @return array{promptText: string, schema: array, meta: array}|JsonResponse
     */
    private function buildPromptPayload(Request $request): array|JsonResponse
    {
        $request->validate([
            'bab_atau_materi'          => 'required|string|max:255',
            'plotting_id'              => 'required|uuid|exists:plottings,id',
            'tujuan_pembelajaran_id'   => 'required|array|min:1',
            // Sesuaikan nama tabel TP di 'exists:' kalau berbeda (mis. tujuan_pembelajarans)
            'tujuan_pembelajaran_id.*' => 'required|uuid|exists:tujuan_pembelajarans,id',
        ]);

        // 1. Susun rencana pertemuan + alokasi waktu dari data Prosem & Plotting
        //    (menggantikan input manual pertemuan_ke / alokasi_waktu)
        $hasilRencana = $this->planner->buildRencanaPertemuan(
            $request->plotting_id,
            $request->tujuan_pembelajaran_id
        );

        if (empty($hasilRencana['rencana'])) {
            return response()->json([
                'message' => 'Tidak ditemukan data Prosem untuk Tujuan Pembelajaran yang dipilih. Pastikan Prosem sudah diisi guru untuk TP ini sebelum membuat Modul Ajar.'
            ], 422);
        }

        $stringRencana     = $this->planner->formatRencanaUntukPrompt($hasilRencana['rencana']);
        $pertemuan         = $hasilRencana['total_pertemuan'];
        $pertemuanAwal     = $hasilRencana['pertemuan_awal'];
        $pertemuanAkhir    = $hasilRencana['pertemuan_akhir'];
        // Label rentang pertemuan ASLI dari Prosem (posisi global dalam tahun
        // ajaran, mis. "27-29" atau "9") untuk diisi ke field form "Pertemuan".
        // JANGAN pakai "1-{$pertemuan}" di frontend, karena $pertemuan cuma
        // jumlah sesi (count), bukan nomor pertemuan sebenarnya.
        $pertemuanLabel    = $hasilRencana['pertemuan_label_global'] ?? '1';
        $jpPerPertemuan    = $hasilRencana['jp_per_pertemuan'];
        $waktuTotalPerSesi = $hasilRencana['total_menit_per_pertemuan'];
        $waktuPendahuluan  = $hasilRencana['menit_pendahuluan'];
        $waktuInti         = $hasilRencana['menit_inti'];
        $waktuPenutup      = $hasilRencana['menit_penutup'];

        // 2. String daftar TP untuk konteks umum di field lain (pertanyaan_pemantik, dll)
        //    Sesuaikan nama kolom 'deskripsi' kalau berbeda di tabel TP Anda.
        $stringTp = TujuanPembelajaran::whereIn('id', $request->tujuan_pembelajaran_id)
            ->get()
            ->values()
            ->map(fn($tp, $i) => ($i + 1) . ". {$tp->deskripsi}")
            ->implode("\n");

        $promptText = <<<PROMPT
Saya sedang membuat Modul Ajar SMK dengan pendekatan Pembelajaran Mendalam (Deep Learning) untuk materi: "{$request->bab_atau_materi}" (Jumlah Pertemuan: {$pertemuan}, Total Alokasi Waktu per Pertemuan: {$waktuTotalPerSesi} menit ({$jpPerPertemuan} JP), dengan pembagian tetap: Pendahuluan {$waktuPendahuluan} menit, Inti {$waktuInti} menit, Penutup {$waktuPenutup} menit).

Tujuan Pembelajarannya adalah:
{$stringTp}

Modul ajar ini menerapkan pendekatan Pembelajaran Mendalam (Deep Learning) dari Kemendikdasmen, yang berpijak pada 3 prinsip utama yang disingkat "BBM":
1. Berkesadaran/Mindful (B) - siswa sadar dan reflektif terhadap apa dan mengapa mereka belajar.
2. Bermakna/Meaningful (B) - materi dikaitkan dengan pengalaman nyata/relevansi kehidupan siswa, khususnya konteks kejuruan/dunia kerja.
3. Menggembirakan/Joyful (M) - proses belajar dibuat menyenangkan, memotivasi, dan melibatkan siswa secara aktif.

Selain 3 prinsip di atas, Pembelajaran Mendalam juga memiliki kerangka Pengalaman Belajar yang terdiri dari 3 tahap berurutan ("3M"), yang WAJIB menjadi struktur inti dari kegiatan_inti:
1. Memahami - peserta didik membangun kesadaran tujuan belajar dan mengonstruksi pemahaman awal terhadap konsep/materi dari berbagai sumber (selaras Taksonomi Bloom: mengingat & memahami; Taksonomi SOLO: unistruktural-multistruktural).
2. Mengaplikasi - peserta didik menerapkan pengetahuan pada situasi nyata/kontekstual: memecahkan masalah, merancang solusi, praktik, atau membuat produk (selaras Bloom: menerapkan & menganalisis; SOLO: relasional).
3. Merefleksi - peserta didik meninjau kembali proses dan hasil belajarnya, mengevaluasi pemahaman, dan menyadari perkembangan dirinya sebagai pembelajar (selaras Bloom: mengevaluasi; SOLO: abstrak-diperluas).

Tolong pastikan prinsip BBM (Berkesadaran, Bermakna, Menggembirakan) DAN tahapan 3M (Memahami-Mengaplikasi-Merefleksi) ini tercermin secara nyata dan konkret pada pertanyaan pemantik, pemahaman bermakna, dan seluruh rangkaian kegiatan pembelajaran (bukan hanya disebut sebagai label, tapi diwujudkan dalam bentuk aktivitas nyata, termasuk pemilihan model pembelajaran yang relevan seperti Discovery Learning, Inquiry Learning, Problem/Project Based Learning, atau praktik kerja langsung yang sesuai karakteristik SMK/vokasi).

Tolong buatkan isian untuk form Modul Ajar saya dengan detail yang cukup kaya dan aplikatif (tidak sekadar poin generik), mengikuti skema JSON yang sudah ditentukan. Untuk field berupa daftar/poin-poin, pisahkan tiap poin dengan karakter baris baru (bukan simbol bullet seperti - atau *, dan jangan pakai markdown seperti ** atau #).

Khusus untuk kegiatan_pendahuluan dan kegiatan_penutup: keduanya WAJIB berupa ARRAY dengan jumlah item dan URUTAN PERSIS SAMA seperti kegiatan_inti (item ke-1 sesuai baris ke-1, item ke-2 sesuai baris ke-2, dst). Setiap item berisi rangkaian kegiatan untuk SATU pertemuan/rentang pertemuan yang bersangkutan — JANGAN membuat satu rangkaian generik yang berlaku untuk semua pertemuan. Jika satu baris mencakup rentang pertemuan (misal "4-6"), pola pendahuluan/penutup yang Anda tuliskan pada item itu berlaku PER SESI dan berulang di tiap pertemuan dalam rentang tersebut. JANGAN menuliskan ulang label/nomor pertemuan atau kode TP di dalam teks item — sistem akan menambahkannya otomatis berdasarkan urutan array. Total estimasi waktu pada TIAP item kegiatan_pendahuluan WAJIB berjumlah persis {$waktuPendahuluan} menit, dan TIAP item kegiatan_penutup WAJIB berjumlah persis {$waktuPenutup} menit (jumlahkan seluruh poin di dalamnya sampai pas dengan angka ini, jangan kurang/lebih).

Khusus untuk kegiatan_inti, ikuti struktur berikut:

Pembagian pertemuan berikut SUDAH DITENTUKAN berdasarkan data Program Semester (Prosem) yang diisi guru (DILARANG diubah, ditambah, dikurangi, digabung ulang, atau dipecah ulang):

{$stringRencana}

kegiatan_inti WAJIB berupa ARRAY OBJEK dengan jumlah item dan URUTAN PERSIS SAMA seperti daftar baris di atas (item ke-1 sesuai baris ke-1, item ke-2 sesuai baris ke-2, dst). JANGAN menuliskan ulang kode TP, nomor pertemuan, atau nama pertemuan di dalam objek manapun — sistem akan menambahkannya secara otomatis berdasarkan urutan array. Tugas Anda pada TIAP OBJEK hanya:
1. Tentukan nama_sub_materi yang ringkas berdasarkan deskripsi TP pada baris terkait.
2. Tentukan model_pembelajaran yang relevan (Discovery Learning, Inquiry Learning, Problem/Project Based Learning, atau praktik kerja langsung, sesuai konteks kejuruan/SMK).
3. Uraikan kegiatan pada 3 tahap Memahami - Mengaplikasi - Merefleksi (tahap_memahami, tahap_mengaplikasi, tahap_merefleksi). Setiap poin kegiatan di dalamnya WAJIB memuat 4 unsur dalam satu baris:
   a) Nama kegiatan singkat
   b) Deskripsi/elaborasi singkat 1 kalimat yang menjelaskan BAGAIMANA kegiatan itu dilaksanakan secara konkret (bukan cuma judul), sesuai konteks kejuruan/dunia kerja
   c) Label prinsip BBM yang paling menonjol pada poin tersebut, ditulis dalam kurung: (Berkesadaran) / (Bermakna) / (Menggembirakan) - usahakan ketiga label BBM tersebar merata di seluruh poin dalam satu objek, tidak menumpuk hanya pada satu tahap saja
   d) Estimasi alokasi waktu

   Format tiap poin (baris baru sebagai pemisah antar poin, tanpa bullet/markdown): [Nama kegiatan] - [deskripsi pelaksanaan] ([Label BBM]) - [estimasi waktu]

PENTING soal waktu: {$waktuInti} menit adalah alokasi waktu KHUSUS kegiatan_inti untuk SATU KALI pertemuan/sesi (di luar pendahuluan {$waktuPendahuluan} menit dan penutup {$waktuPenutup} menit yang sudah ditulis terpisah). Jika satu baris mencakup beberapa pertemuan (misal rentang "4-6" berarti 3 pertemuan), maka pola kegiatan Memahami-Mengaplikasi-Merefleksi yang Anda tuliskan pada objek tersebut adalah pola yang terjadi PER SESI dan berulang/berkembang di tiap pertemuan pada rentang itu (bukan dibagi rata dari satu kelompok besar). Pastikan total estimasi waktu pada tahap_memahami+tahap_mengaplikasi+tahap_merefleksi di TIAP OBJEK berjumlah persis {$waktuInti} menit, bukan dikalikan atau dibagi jumlah pertemuan dalam baris tersebut.
PROMPT;

        // Skema JSON supaya hasil dari Gemini terstruktur & langsung bisa
        // dipetakan ke field form (bukan teks bebas yang harus di-parse manual).
        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'pertanyaan_pemantik' => [
                    'type' => 'STRING',
                    'description' => '1-2 pertanyaan singkat pemancing nalar siswa, dirancang agar bersifat Berkesadaran/mindful (mendorong siswa sadar akan tujuan belajarnya) dan Bermakna/meaningful (terkait konteks nyata/dunia kerja SMK), sebagai bagian dari prinsip BBM',
                ],
                'pemahaman_bermakna' => [
                    'type' => 'STRING',
                    'description' => '1-5 kalimat singkat manfaat materi di dunia nyata/dunia kerja, mencerminkan prinsip Bermakna (bagian dari BBM)',
                ],
                'sarana_prasarana' => [
                    'type' => 'STRING',
                    'description' => 'Daftar sarana prasarana yang dibutuhkan, satu per baris. Contoh: Laptop, Proyektor, Papan Tulis, Buku Paket, Peralatan praktik',
                ],
                'identifikasi_murid' => [
                    'type' => 'STRING',
                    'description' => 'Komponen A.1 Murid (Juknis KSP): deskripsi kesiapan peserta didik sebelum belajar — pengetahuan awal, minat, latar belakang, dan kebutuhan belajar, serta aspek lainnya (bisa dilakukan menggunakan asesmen awal). Konkret sesuai konteks materi & TP, satu poin per baris',
                ],
                'analisis_materi' => [
                    'type' => 'STRING',
                    'description' => 'Komponen A.2 Materi Pelajaran (Juknis KSP): analisis materi pelajaran — jenis pengetahuan yang akan dicapai, relevansi dengan kehidupan nyata murid, tingkat kesulitan, struktur materi, serta integrasi nilai dan karakter. Satu poin per baris',
                ],
                'kemitraan' => [
                    'type' => 'STRING',
                    'description' => 'Komponen B.6 Kemitraan Pembelajaran (Juknis KSP): mitra kerjasama untuk berkolaborasi dan berperan dalam pembelajaran — lingkungan sekolah, lingkungan luar sekolah, masyarakat, guru bidang studi lain, orang tua, komunitas, tokoh masyarakat, dunia usaha dan dunia industri (DU/DI), institusi, atau mitra profesional. Relevan dengan konteks SMK, satu poin per baris',
                ],
                'lingkungan_belajar' => [
                    'type' => 'STRING',
                    'description' => 'Komponen B.7 Lingkungan Pembelajaran (Juknis KSP): lingkungan pembelajaran yang mengintegrasikan ruang fisik, ruang virtual, dan budaya belajar untuk mendukung pembelajaran mendalam. Contoh: lingkungan sekolah, LMS (Learning Management System), dukungan guru. Satu poin per baris',
                ],
                'pemanfaatan_digital' => [
                    'type' => 'STRING',
                    'description' => 'Komponen B.8 Pemanfaatan Digital (Juknis KSP): pemanfaatan teknologi digital untuk menciptakan pembelajaran yang lebih interaktif, kolaboratif, dan kontekstual. Contoh: perpustakaan digital, forum diskusi daring, penilaian daring. Satu poin per baris',
                ],
                'lkpd' => [
                    'type' => 'STRING',
                    'description' => 'Ide tugas praktek/teori untuk siswa yang cukup detail, dikemas agar Menggembirakan/joyful (menarik, interaktif) dan Bermakna/meaningful (relevan dunia kerja) sesuai prinsip BBM, idealnya terkait tahap Mengaplikasi pada kegiatan_inti',
                ],
                'glosarium_pustaka' => [
                    'type' => 'STRING',
                    'description' => '3-4 istilah kunci + definisi singkat, dan 1-2 referensi/buku umum, satu per baris',
                ],
                'kegiatan_pendahuluan' => [
                    'type' => 'ARRAY',
                    'description' => 'Array dengan jumlah & urutan PERSIS SAMA seperti kegiatan_inti (item ke-1 = baris rencana ke-1, dst). TIAP item = rangkaian kegiatan Pendahuluan untuk SATU pertemuan/rentang pertemuan (bukan generik untuk semua pertemuan). Satu poin per baris, sertakan estimasi alokasi waktu tiap poin, dengan TOTAL seluruh poin pada tiap item harus persis sama dengan waktuPendahuluan yang diberikan. Sisipkan unsur Berkesadaran (misal refleksi singkat/menyampaikan tujuan) dan Menggembirakan (ice breaking/apersepsi menarik) sesuai prinsip BBM, hindari poin klise generik. JANGAN tulis label/nomor pertemuan di dalam teks.',
                    'items' => [
                        'type' => 'STRING',
                    ],
                ],
                'kegiatan_inti' => [
                    'type' => 'ARRAY',
                    'description' => 'Array objek dengan jumlah & urutan PERSIS SAMA seperti daftar pembagian pertemuan yang diberikan di prompt (item ke-1 = baris ke-1, dst). JANGAN sertakan kode TP atau nomor pertemuan di dalam objek ini.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'nama_sub_materi' => [
                                'type' => 'STRING',
                                'description' => 'Nama sub-materi ringkas untuk baris pertemuan ini, berdasarkan deskripsi TP terkait',
                            ],
                            'model_pembelajaran' => [
                                'type' => 'STRING',
                                'description' => 'Model pembelajaran yang relevan, contoh: Discovery Learning, Inquiry Learning, Problem Based Learning, Project Based Learning, atau praktik kerja langsung sesuai konteks SMK/vokasi',
                            ],
                            'tahap_memahami' => [
                                'type' => 'STRING',
                                'description' => 'Poin-poin kegiatan tahap Memahami (3M), satu poin per baris, format: "[nama kegiatan] - [deskripsi pelaksanaan] ([label BBM]) - [estimasi waktu]"',
                            ],
                            'tahap_mengaplikasi' => [
                                'type' => 'STRING',
                                'description' => 'Poin-poin kegiatan tahap Mengaplikasi (3M), satu poin per baris, format sama seperti tahap_memahami',
                            ],
                            'tahap_merefleksi' => [
                                'type' => 'STRING',
                                'description' => 'Poin-poin kegiatan tahap Merefleksi (3M), satu poin per baris, format sama seperti tahap_memahami',
                            ],
                        ],
                        'required' => ['nama_sub_materi', 'model_pembelajaran', 'tahap_memahami', 'tahap_mengaplikasi', 'tahap_merefleksi'],
                    ],
                ],
                'kegiatan_penutup' => [
                    'type' => 'ARRAY',
                    'description' => 'Array dengan jumlah & urutan PERSIS SAMA seperti kegiatan_inti (item ke-1 = baris rencana ke-1, dst). TIAP item = rangkaian kegiatan Penutup untuk SATU pertemuan/rentang pertemuan (bukan generik untuk semua pertemuan). Satu poin per baris, sertakan estimasi alokasi waktu tiap poin, dengan TOTAL seluruh poin pada tiap item harus persis sama dengan waktuPenutup yang diberikan. Sisipkan unsur refleksi (Berkesadaran) dan penguatan motivasi (Menggembirakan) sesuai prinsip BBM, hindari poin klise generik. JANGAN tulis label/nomor pertemuan di dalam teks.',
                    'items' => [
                        'type' => 'STRING',
                    ],
                ],
                'rekomendasi_asesmen' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'STRING',
                        'enum' => ['Diagnostik', 'Formatif', 'Sumatif'],
                    ],
                    'description' => 'Jenis asesmen yang direkomendasikan untuk dicentang, berdasarkan skenario pembelajaran',
                ],
                'remedial_content' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin singkat langkah remedial konkret, satu per baris',
                ],
                'enrichment_content' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin singkat bentuk evaluasi pengayaan konkret, satu per baris',
                ],
            ],
            'required' => [
                'pertanyaan_pemantik',
                'pemahaman_bermakna',
                'sarana_prasarana',
                'identifikasi_murid',
                'analisis_materi',
                'kemitraan',
                'lingkungan_belajar',
                'pemanfaatan_digital',
                'lkpd',
                'glosarium_pustaka',
                'kegiatan_pendahuluan',
                'kegiatan_inti',
                'kegiatan_penutup',
                'rekomendasi_asesmen',
                'remedial_content',
                'enrichment_content',
            ],
        ];

        $meta = [
            'pertemuan' => $pertemuan, // jumlah SESI (count) modul ini, dipakai di teks prompt "Jumlah Pertemuan: N"
            'pertemuan_awal' => $pertemuanAwal,
            'pertemuan_akhir' => $pertemuanAkhir,
            // Field yang dipakai untuk isi form "Pertemuan" di frontend, mis. "27-29".
            // JANGAN dibentuk ulang di frontend sebagai "1-{pertemuan}" -- pakai field ini langsung.
            'pertemuan_label' => $pertemuanLabel,
            'jp_per_pertemuan' => $jpPerPertemuan,
            'waktu_total_per_sesi_menit' => $waktuTotalPerSesi,
            'waktu_pendahuluan_menit' => $waktuPendahuluan,
            'waktu_inti_menit' => $waktuInti,
            'waktu_penutup_menit' => $waktuPenutup,
        ];

        return [
            'promptText' => $promptText,
            'schema' => $schema,
            'meta' => $meta,
            'rencana' => $hasilRencana['rencana'],
        ];
    }

    /**
     * Ambil DPL (Dimensi Profil Lulusan) & Asesmen yang SUDAH diisi guru di tabel
     * ATP untuk daftar TP terpilih (rantai KSP: CP -> Analisis CP -> TP -> ATP).
     * Nilai dipecah koma, di-unique, dan dinormalisasi supaya pas dengan label
     * dropdown DPL & checkbox asesmen di form modul ajar.
     */
    private function sinkronDplAsesmenDariAtp(array $tpIds): array
    {
        $tpIds = array_values(array_filter($tpIds));
        if (empty($tpIds)) {
            return [[], []];
        }

        $atpData = \App\Models\Atp::whereIn('tujuan_pembelajaran_id', $tpIds)
            ->get(['dpl', 'asesmen']);

        $kumpulkan = function ($values) {
            return collect($values)
                ->map(fn ($v) => array_map('trim', explode(',', (string) $v)))
                ->flatten()
                ->filter(fn ($v) => $v !== '' && $v !== null)
                ->unique()
                ->values()
                ->all();
        };

        return [
            $kumpulkan($atpData->pluck('dpl')->all()),
            $kumpulkan($atpData->pluck('asesmen')->all()),
        ];
    }

    /**
     * Gabungkan hasil array dari AI (tanpa label pertemuan / kode TP) dengan data
     * rencana pertemuan dari Prosem (yang punya label & kode TP pasti), dicocokkan
     * berdasarkan URUTAN index array. Hasilnya array PER PERTEMUAN:
     * [{ pertemuan_label, kode_tp, nama_sub_materi, model_pembelajaran,
     *    pendahuluan, inti, penutup }] — siap dipetakan langsung ke form
     * kegiatan pembelajaran modul ajar.
     */
    private function buildKegiatanPerPertemuan(array $rencana, array $itemsInti, array $itemsPendahuluan, array $itemsPenutup): array
    {
        $blocks = [];

        foreach ($rencana as $index => $r) {
            $item = $itemsInti[$index] ?? null;
            if (!$item) {
                // Guard: kalau AI kasih jumlah item lebih sedikit dari rencana,
                // jangan sampai error, cukup lewati baris ini.
                continue;
            }

            // Label pertemuan memakai posisi GLOBAL di Prosem (mis. "Pertemuan
            // 12-20"), supaya skenario kegiatan sesuai dengan yang guru lihat di
            // Prosem -- bukan penomoran lokal yang mulai dari 1 lagi.
            $mulaiGlobal = $r['pertemuan_mulai_global'] ?? $r['pertemuan_mulai'];
            $selesaiGlobal = $r['pertemuan_selesai_global'] ?? $r['pertemuan_selesai'];
            $label = $mulaiGlobal === $selesaiGlobal
                ? "Pertemuan {$mulaiGlobal}"
                : "Pertemuan {$mulaiGlobal}-{$selesaiGlobal}";

            $blocks[] = [
                'pertemuan_label' => $label,
                'kode_tp' => $r['kode_tp'],
                'nama_sub_materi' => $item['nama_sub_materi'] ?? $r['deskripsi_tp'],
                'model_pembelajaran' => $item['model_pembelajaran'] ?? '-',
                'pendahuluan' => trim($itemsPendahuluan[$index] ?? ''),
                'inti' => trim(
                    "Tahap Memahami:\n" . ($item['tahap_memahami'] ?? '')
                    . "\n\nTahap Mengaplikasi:\n" . ($item['tahap_mengaplikasi'] ?? '')
                    . "\n\nTahap Merefleksi:\n" . ($item['tahap_merefleksi'] ?? '')
                ),
                'penutup' => trim($itemsPenutup[$index] ?? ''),
            ];
        }

        return $blocks;
    }
}

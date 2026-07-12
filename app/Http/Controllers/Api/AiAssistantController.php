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

Khusus untuk kegiatan_pendahuluan dan kegiatan_penutup: buat SATU rangkaian kegiatan generik yang berlaku sama untuk SEMUA pertemuan (tidak perlu dipecah per pertemuan), karena pola pembukaan dan penutupan kelas umumnya konsisten setiap sesi. Tetap detail dan konkret, bukan poin klise seperti "guru membuka pelajaran dengan salam". Total estimasi waktu pada kegiatan_pendahuluan WAJIB berjumlah persis {$waktuPendahuluan} menit, dan kegiatan_penutup WAJIB berjumlah persis {$waktuPenutup} menit (jumlahkan seluruh poin di dalamnya sampai pas dengan angka ini, jangan kurang/lebih).

Khusus untuk kegiatan_inti, ikuti struktur berlapis berikut:

LAPIS 1 - Pembagian pertemuan berikut SUDAH DITENTUKAN berdasarkan data Program Semester (Prosem) yang diisi guru, dan WAJIB diikuti PERSIS seperti apa adanya (DILARANG menambah, mengurangi, menggabungkan ulang, memecah ulang, atau mengubah rentang pertemuan maupun kode TP di bawah ini):

{$stringRencana}

Tugas Anda pada LAPIS 1 ini HANYA menentukan nama sub-materi yang ringkas dan model pembelajaran yang relevan untuk tiap baris di atas (berdasarkan deskripsi TP yang diberikan), TANPA mengubah rentang pertemuan atau kode TP yang sudah ditentukan.

LAPIS 2 - Di dalam tiap kelompok pertemuan, uraikan kegiatan mengikuti 3 tahap Memahami - Mengaplikasi - Merefleksi. Setiap poin kegiatan WAJIB memuat 4 unsur berikut dalam satu baris:
a) Nama kegiatan singkat
b) Deskripsi/elaborasi singkat 1 kalimat yang menjelaskan BAGAIMANA kegiatan itu dilaksanakan secara konkret (bukan cuma judul), sesuai konteks kejuruan/dunia kerja
c) Label prinsip BBM yang paling menonjol pada poin tersebut, ditulis dalam kurung: (Berkesadaran) / (Bermakna) / (Menggembirakan) - usahakan ketiga label BBM tersebar merata di seluruh poin kegiatan_inti, tidak menumpuk hanya pada satu tahap saja
d) Estimasi alokasi waktu

Format tiap poin: [Nama kegiatan] - [deskripsi pelaksanaan] ([Label BBM]) - [estimasi waktu]

Setiap judul kelompok pertemuan WAJIB menggunakan persis rentang pertemuan dan kode TP yang sudah diberikan pada LAPIS 1 di atas (dari data Prosem), dengan format: "Pertemuan X-Y [Kode TP: ...]: [nama sub-materi] (Model: [nama model pembelajaran])".

Format keluaran kegiatan_inti mengikuti pola berikut (gunakan baris baru antar poin, tanpa bullet/markdown):

Pertemuan 1-2 [Kode TP: sebutkan kode TP terkait]: [nama sub-materi] (Model: [nama model pembelajaran yang dipakai])
Tahap Memahami:
[Nama kegiatan] - [deskripsi pelaksanaan] (Berkesadaran) - [estimasi waktu]
[Nama kegiatan] - [deskripsi pelaksanaan] (Bermakna) - [estimasi waktu]
Tahap Mengaplikasi:
[Nama kegiatan] - [deskripsi pelaksanaan] (Menggembirakan) - [estimasi waktu]
[Nama kegiatan] - [deskripsi pelaksanaan] (Bermakna) - [estimasi waktu]
Tahap Merefleksi:
[Nama kegiatan] - [deskripsi pelaksanaan] (Berkesadaran) - [estimasi waktu]

Pertemuan 3 [Kode TP: sebutkan kode TP terkait]: [nama sub-materi lain] (Model: [nama model pembelajaran yang dipakai])
Tahap Memahami:
...
Tahap Mengaplikasi:
...
Tahap Merefleksi:
...

Lanjutkan pola ini untuk seluruh {$pertemuan} pertemuan sesuai pembagian pada LAPIS 1. PENTING soal waktu: {$waktuInti} menit adalah alokasi waktu KHUSUS kegiatan_inti untuk SATU KALI pertemuan/sesi (di luar pendahuluan {$waktuPendahuluan} menit dan penutup {$waktuPenutup} menit yang sudah ditulis terpisah). Jika satu kelompok mencakup beberapa pertemuan (misal "Pertemuan 1-3"), maka pola kegiatan Memahami-Mengaplikasi-Merefleksi yang Anda tuliskan adalah pola yang terjadi PER SESI dan berulang/berkembang secara bertahap di tiap pertemuan pada rentang tersebut (bukan dibagi rata dari satu kelompok besar). Pastikan total estimasi waktu pada kegiatan di SETIAP SESI (Memahami+Mengaplikasi+Merefleksi) berjumlah persis {$waktuInti} menit, bukan dikalikan atau dibagi jumlah pertemuan dalam kelompok tersebut.
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
                    'description' => 'Daftar singkat alat/bahan/media, satu item per baris',
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
                    'type' => 'STRING',
                    'description' => 'Poin-poin detail kegiatan Pendahuluan yang BERLAKU SAMA untuk semua pertemuan (tidak dipecah per pertemuan), satu poin per baris, sertakan estimasi alokasi waktu tiap poin, dengan TOTAL seluruh poin harus persis sama dengan waktuPendahuluan yang diberikan. Sisipkan unsur Berkesadaran (misal refleksi singkat/menyampaikan tujuan) dan Menggembirakan (ice breaking/apersepsi menarik) sesuai prinsip BBM, hindari poin klise generik',
                ],
                'kegiatan_inti' => [
                    'type' => 'STRING',
                    'description' => 'Kegiatan Inti mengikuti PERSIS pembagian rentang pertemuan dan kode TP yang sudah ditentukan dari data Prosem (jangan diubah/digabung/dipecah ulang), judul tiap kelompok formatnya: "Pertemuan 1-2 [Kode TP: ...]: [sub-materi] (Model: [nama model])". Di dalam tiap kelompok pertemuan WAJIB diuraikan dalam 3 sub-tahap 3M: Tahap Memahami, Tahap Mengaplikasi, Tahap Merefleksi. Setiap poin kegiatan WAJIB berformat "[nama kegiatan] - [deskripsi singkat cara pelaksanaan] ([label BBM: Berkesadaran/Bermakna/Menggembirakan]) - [estimasi waktu]", dengan label BBM tersebar merata di seluruh poin (bukan menumpuk di satu tahap). Total estimasi waktu tiap SESI (bukan tiap kelompok rentang pertemuan) harus persis sama dengan waktuInti yang diberikan (di luar waktu pendahuluan dan penutup). Satu baris untuk tiap judul pertemuan/tahap/poin kegiatan (baris baru sebagai pemisah, tanpa bullet/markdown)',
                ],
                'kegiatan_penutup' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin detail kegiatan Penutup yang BERLAKU SAMA untuk semua pertemuan (tidak dipecah per pertemuan), satu poin per baris, sertakan estimasi alokasi waktu tiap poin, dengan TOTAL seluruh poin harus persis sama dengan waktuPenutup yang diberikan. Sisipkan unsur refleksi (Berkesadaran) dan penguatan motivasi (Menggembirakan) sesuai prinsip BBM, hindari poin klise generik',
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
            'pertemuan' => $pertemuan,
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
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
 * Cara pakai:
 * 1. Daftar & ambil API key gratis di https://aistudio.google.com
 * 2. Tambahkan di .env:  GEMINI_API_KEY=isi_key_anda_disini
 * 3. Daftarkan route (lihat contoh di routes_example.php)
 */
class AiAssistantController extends Controller
{
    public function generateModul(Request $request)
    {
        $request->validate([
            'bab_atau_materi'        => 'required|string|max:255',
            'pertemuan_ke'           => 'nullable|string|max:50',
            'alokasi_waktu'          => 'nullable|string|max:50',
            'tujuan_pembelajaran'    => 'required|array|min:1',
            'tujuan_pembelajaran.*'  => 'string',
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'message' => 'GEMINI_API_KEY belum diatur di server (.env). Hubungi admin aplikasi.'
            ], 500);
        }

        $waktu = $request->alokasi_waktu ? $request->alokasi_waktu . ' Menit' : 'Sesuai jam pelajaran';
        $pertemuan = $request->pertemuan_ke ?: '-';

        $stringTp = collect($request->tujuan_pembelajaran)
            ->map(fn($tp, $i) => ($i + 1) . ". {$tp}")
            ->implode("\n");

        $promptText = <<<PROMPT
Saya sedang membuat Modul Ajar SMK untuk materi: "{$request->bab_atau_materi}" (Pertemuan: {$pertemuan}, Waktu: {$waktu}).

Tujuan Pembelajarannya adalah:
{$stringTp}

Tolong buatkan isian untuk form Modul Ajar saya, dengan bahasa yang sederhana, langsung pada intinya (to the point), dan tidak kompleks. Ikuti skema JSON yang sudah ditentukan. Untuk field berupa daftar/poin-poin, pisahkan tiap poin dengan karakter baris baru (bukan simbol bullet seperti - atau *, dan jangan pakai markdown seperti ** atau #).
PROMPT;

        // Skema JSON supaya hasil dari Gemini terstruktur & langsung bisa
        // dipetakan ke field form (bukan teks bebas yang harus di-parse manual).
        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'pertanyaan_pemantik' => [
                    'type' => 'STRING',
                    'description' => '1-2 pertanyaan singkat pemancing nalar siswa',
                ],
                'pemahaman_bermakna' => [
                    'type' => 'STRING',
                    'description' => '1-2 kalimat singkat manfaat materi di dunia nyata',
                ],
                'sarana_prasarana' => [
                    'type' => 'STRING',
                    'description' => 'Daftar singkat alat/bahan/media, satu item per baris',
                ],
                'lkpd' => [
                    'type' => 'STRING',
                    'description' => 'Ide singkat tugas praktek/teori untuk siswa',
                ],
                'glosarium_pustaka' => [
                    'type' => 'STRING',
                    'description' => '3-4 istilah kunci + definisi singkat, dan 1-2 referensi/buku umum, satu per baris',
                ],
                'kegiatan_pendahuluan' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin detail kegiatan Pendahuluan, satu poin per baris, sertakan estimasi alokasi waktu tiap poin',
                ],
                'kegiatan_inti' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin SANGAT detail kegiatan Inti (bisa dipecah per pertemuan), satu poin per baris, sertakan estimasi alokasi waktu',
                ],
                'kegiatan_penutup' => [
                    'type' => 'STRING',
                    'description' => 'Poin-poin detail kegiatan Penutup, satu poin per baris, sertakan estimasi alokasi waktu',
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

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $promptText]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => $schema,
                        'temperature'      => 0.7,
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

            return response()->json($hasil);
        } catch (\Exception $e) {
            Log::error('Gemini request exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghubungi layanan AI.'
            ], 500);
        }
    }
}

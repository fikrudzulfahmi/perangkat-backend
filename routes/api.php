<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuruController;
use App\Http\Controllers\Api\MataPelajaranController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\TahunPelajaranController;
use App\Http\Controllers\Api\PlottingController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\KalenderEfektifController;
use App\Http\Controllers\Api\CapaianPembelajaranController;
use App\Http\Controllers\Api\TujuanPembelajaranController;
use App\Http\Controllers\Api\AtpGuruController;
use App\Http\Controllers\Api\KktpGuruController;
use App\Http\Controllers\Api\ProsemGuruController;
use App\Http\Controllers\Api\BukuPeganganController;
use App\Http\Controllers\Api\BankSoalController;
use App\Http\Controllers\Api\ModulAjarController;
use App\Http\Controllers\Api\DokumenStatisController;
use App\Http\Controllers\Api\JadwalMengajarController;

// ==========================================
// PUBLIC ROUTES (Tidak butuh login)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// PROTECTED ROUTES (Wajib login Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // 1. GLOBAL ROUTE (Bisa diakses Admin, Guru, dll selama punya token)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 3. GURU ROUTES (Nanti kita isi saat membuat fitur khusus guru)
    Route::middleware(['role_api:guru'])->group(function () {
        // Sesuaikan nama method di controller Anda (index atau myPlotting)
        // Route::get('/guru/plotting', [PlottingController::class, 'index']);
        Route::get('/guru/plotting', [PlottingController::class, 'myPlotting']);
        Route::get('/guru/capaian-pembelajaran', [CapaianPembelajaranController::class, 'getStructureForGuru']);
        Route::get('/guru/atp', [AtpGuruController::class, 'getAtp']);
        Route::post('/guru/atp', [AtpGuruController::class, 'saveAtp']);
        Route::get('/guru/mapel', [MataPelajaranController::class, 'index']);
        Route::get('/guru/kelas', [KelasController::class, 'index']);
        Route::get('/guru/rme/total-minggu', function (\Illuminate\Http\Request $request) {
            $tahunId = $request->query('tahun_pelajaran_id');

            // Menjumlahkan kolom 'minggu_efektif' berdasarkan tahun pelajaran
            $totalMinggu = \App\Models\KalenderEfektif::where('tahun_pelajaran_id', $tahunId)
                ->sum('minggu_efektif');

            return response()->json(['total_minggu_efektif' => $totalMinggu]);
        });
        Route::get('/guru/kktp', [KktpGuruController::class, 'getKktp']);
        Route::post('/guru/kktp', [KktpGuruController::class, 'saveKktp']);
        Route::get('/guru/prosem', [ProsemGuruController::class, 'getProsem']);
        Route::post('/guru/prosem/save', [ProsemGuruController::class, 'saveProsem']);

        Route::get('/guru/buku-pegangan', [BukuPeganganController::class, 'index']);
        Route::post('/guru/buku-pegangan', [BukuPeganganController::class, 'store']);
        Route::put('/guru/buku-pegangan/{id}', [BukuPeganganController::class, 'update']);
        Route::delete('/guru/buku-pegangan/{id}', [BukuPeganganController::class, 'destroy']);

        Route::get('/guru/bank-soal', [BankSoalController::class, 'index']);
        Route::post('/guru/bank-soal', [BankSoalController::class, 'store']);
        Route::put('/guru/bank-soal/{id}', [BankSoalController::class, 'update']);
        Route::delete('/guru/bank-soal/{id}', [BankSoalController::class, 'destroy']);
        Route::post('/guru/bank-soal/import', [BankSoalController::class, 'import']);
        Route::get('/guru/bank-soal/template', [BankSoalController::class, 'downloadTemplate']);

        Route::apiResource('/guru/modul-ajar', ModulAjarController::class);
        Route::get('/guru/dokumen-statis', [DokumenStatisController::class, 'index']);
        Route::get('/guru/kalender-efektif', [KalenderEfektifController::class, 'index']);
        Route::get('/guru/jadwal-mengajar', [JadwalMengajarController::class, 'index']);
        Route::get('/guru/siswa', [SiswaController::class, 'index']);

        // Cari rute ini dan ubah bagian 'index' menjadi 'allUntukGuru'
        Route::get('/guru/tahun-ajaran', [TahunPelajaranController::class, 'allUntukGuru']);
        Route::get('/guru/atp/referensi-teman', [AtpGuruController::class, 'referensiTeman']);
        Route::get('/guru/atp/ambil-teman', [AtpGuruController::class, 'ambilAtpTeman']);
        Route::get('/guru/prosem/referensi-teman', [ProsemGuruController::class, 'referensiTeman']);
        Route::get('/guru/prosem/ambil-teman', [ProsemGuruController::class, 'ambilProsemTeman']);
        Route::get('/guru/buku-pegangan/referensi-global', [BukuPeganganController::class, 'referensiGlobal']);
        // Route untuk Kloning Bank Soal
        Route::get('/guru/bank-soal/referensi', [BankSoalController::class, 'referensiSoal']);
        Route::post('/guru/bank-soal/kloning-selektif', [BankSoalController::class, 'kloningSelektif']);
        Route::get('/guru/bank-soal/referensi-plotting', [BankSoalController::class, 'referensiPlotting']);
    });

    // 2. ADMIN ROUTES (Hanya bisa diakses oleh role 'admin')
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('guru', GuruController::class);
        Route::apiResource('mapel', MataPelajaranController::class);
        Route::apiResource('kelas', KelasController::class);
        Route::apiResource('tahun-pelajaran', TahunPelajaranController::class)->except(['show']);
        Route::apiResource('plotting', PlottingController::class)->except(['show']);
        Route::apiResource('siswa', SiswaController::class)->only(['index', 'store']);
        Route::post('siswa/bulk-delete', [SiswaController::class, 'bulkDelete']);
        Route::apiResource('kalender-efektif', KalenderEfektifController::class)->only(['index', 'store']);
        Route::apiResource('capaian-pembelajaran', CapaianPembelajaranController::class);
        Route::apiResource('tujuan-pembelajaran', TujuanPembelajaranController::class)->except(['index', 'show']);
        Route::apiResource('dokumen-statis', DokumenStatisController::class)->only([
            'index',
            'store'
        ]);
        Route::apiResource('jadwal-mengajar', JadwalMengajarController::class);
    });
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BankSoalService;
use App\Http\Requests\StoreBankSoalRequest;
use App\Http\Requests\UpdateBankSoalRequest;
use App\Http\Resources\BankSoalResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BankSoalImport;
use App\Models\Plotting;
use App\Exports\BankSoalTemplateExport;

class BankSoalController extends Controller
{
    protected $soalService;

    public function __construct(BankSoalService $soalService)
    {
        $this->soalService = $soalService;
    }

    public function index(Request $request)
    {
        // Mendukung filter by plotting_id dari Vue via query string (?plotting_id=xxx)
        $soals = $this->soalService->getSoalByGuru(
            $request->user()->id,
            $request->query('plotting_id')
        );
        return BankSoalResource::collection($soals);
    }

    public function store(StoreBankSoalRequest $request)
    {
        $soal = $this->soalService->createSoal($request->validated());

        return response()->json([
            'message' => 'Soal berhasil disimpan',
            'data'    => new BankSoalResource($soal)
        ], 201);
    }

    public function update(UpdateBankSoalRequest $request, $id)
    {
        try {
            $soal = $this->soalService->updateSoal($id, $request->user()->id, $request->validated());
            return response()->json([
                'message' => 'Soal berhasil diperbarui',
                'data'    => new BankSoalResource($soal)
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->soalService->deleteSoal($id, $request->user()->id);
            return response()->json(['message' => 'Soal berhasil dihapus'], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'plotting_id' => 'required|uuid|exists:plottings,id',
            'file'        => 'required|mimes:xlsx,xls,csv|max:2048' // max 2MB
        ]);

        // Cek keamanan: pastikan plotting ini milik guru yang login
        $plotting = Plotting::findOrFail($request->plotting_id);
        if ($plotting->guru_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke Mata Pelajaran ini.'], 403);
        }

        try {
            Excel::import(new BankSoalImport($request->plotting_id), $request->file('file'));
            return response()->json(['message' => 'Bank Soal berhasil diimport dari Excel!'], 200);
        } catch (\Exception $e) {
            Log::error("Error Import Excel Soal: " . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses file Excel. Pastikan format kolom sesuai template.'], 500);
        }
    }
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'plotting_id' => 'required|uuid|exists:plottings,id'
        ]);

        $plotting = Plotting::findOrFail($request->plotting_id);
        $namaFile = 'template_soal_' . str_replace(' ', '_', strtolower($plotting->mapel)) . '.xlsx';

        return Excel::download(new BankSoalTemplateExport($request->plotting_id), $namaFile);
    }
    public function referensiSoal(Request $request)
    {
        $plottingId = $request->query('plotting_id');

        // Ambil soal berdasarkan plotting yang dipilih di masa lalu
        $soal = \App\Models\BankSoal::where('plotting_id', $plottingId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $soal
        ]);
    }

    public function kloningSelektif(Request $request)
    {
        $request->validate([
            'soal_ids' => 'required|array',
            'target_plotting_id' => 'required'
        ]);

        $soalIds = $request->soal_ids;
        $targetPlottingId = $request->target_plotting_id;
        $jumlahBerhasil = 0;

        // Ambil semua soal yang di-ceklis
        $soals = \App\Models\BankSoal::whereIn('id', $soalIds)->get();

        foreach ($soals as $soal) {
            $soalBaru = $soal->replicate(); // Duplikasi row
            $soalBaru->plotting_id = $targetPlottingId; // Arahkan ke tugas mengajar saat ini
            $soalBaru->tp_id = null; // Reset relasi Tujuan Pembelajaran (karena TP tiap tahun bisa beda)
            $soalBaru->save();

            $jumlahBerhasil++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "$jumlahBerhasil soal berhasil disalin ke bank soal aktif!"
        ]);
    }

    public function referensiPlotting(Request $request)
    {
        $plottingIdKita = $request->query('plotting_id');
        $tahunPelajaranId = $request->query('tahun_pelajaran_id');

        // Cari tahu mapel_id dari plotting kita saat ini
        $plottingKita = \App\Models\Plotting::find($plottingIdKita);

        if (!$plottingKita) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Cari plotting milik orang lain dengan mapel_id yang sama
        // CATATAN: Pastikan relasi 'guru' dan 'kelas' sesuai dengan nama di Model Plotting Anda
        $referensi = \App\Models\Plotting::with(['guru', 'listKelas'])
            ->where('mapel_id', $plottingKita->mapel_id)
            ->where('tahun_pelajaran_id', $tahunPelajaranId)
            ->where('id', '!=', $plottingIdKita)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $referensi
        ]);
    }
}

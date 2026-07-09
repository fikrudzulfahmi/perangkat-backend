<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetProsemRequest;
use App\Http\Requests\SaveProsemRequest;
use App\Http\Resources\ProsemStructureResource; // <-- Jangan lupa import ini
use App\Services\ProsemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProsemGuruController extends Controller
{
    protected ProsemService $prosemService;

    public function __construct(ProsemService $prosemService)
    {
        $this->prosemService = $prosemService;
    }

    // Ubah return type-nya menjadi Resource
    public function getProsem(GetProsemRequest $request): ProsemStructureResource
    {
        $data = $this->prosemService->getProsemStructure($request->validated('plotting_id'));

        // Gunakan Resource untuk merapikan response JSON
        return new ProsemStructureResource($data);
    }

    public function saveProsem(SaveProsemRequest $request): JsonResponse
    {
        try {
            $this->prosemService->saveProsemData(
                $request->validated('plotting_id'),
                $request->validated('items')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data Program Semester (Prosem) berhasil disimpan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
    public function referensiTeman(Request $request)
    {
        $plottingIdKita = $request->query('plotting_id');
        $tahunPelajaranId = $request->query('tahun_pelajaran_id');

        $plottingKita = \App\Models\Plotting::find($plottingIdKita);
        if (!$plottingKita) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Mencari guru lain yang mengajar mapel yang sama pada tahun ajaran yang dipilih
        $referensi = \App\Models\Plotting::with(['guru']) // list_kelas otomatis termuat jika merupakan Accessor ($appends)
            ->where('mapel_id', $plottingKita->mapel_id)
            ->where('tahun_pelajaran_id', $tahunPelajaranId)
            ->where('id', '!=', $plottingIdKita)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $referensi
        ]);
    }

    public function ambilProsemTeman(Request $request)
    {
        $plottingIdTeman = $request->query('plotting_id_teman');

        // Mengambil seluruh baris distribusi JP milik rekan guru berdasarkan plotting_id mereka
        // *Silakan sesuaikan nama Model Prosem Anda jika berbeda (misal: ProgramSemester atau Prosem)*
        $prosemTeman = \App\Models\Prosem::where('plotting_id', $plottingIdTeman)->get();

        return response()->json([
            'status' => 'success',
            'data' => $prosemTeman
        ]);
    }
}

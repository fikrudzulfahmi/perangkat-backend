<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plotting;
use App\Http\Requests\PlottingRequest;
use App\Http\Resources\PlottingResource;
use App\Services\PlottingService;

class PlottingController extends Controller
{
    protected $plottingService;
    public function __construct(PlottingService $plottingService)
    {
        $this->plottingService = $plottingService;
    }

    public function index(Request $request)
    {
        // Ambil parameter dari request url (?tahun_pelajaran_id=...&search=...&per_page=...)
        $tahunPelajaranId = $request->query('tahun_pelajaran_id');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $data = $this->plottingService->ambilPaginasi($perPage, $tahunPelajaranId, $search);
        return PlottingResource::collection($data);
    }

    public function store(PlottingRequest $request)
    {
        $plotting = $this->plottingService->buatBaru($request->validated());
        return new PlottingResource($plotting);
    }

    public function update(PlottingRequest $request, $id)
    {
        $plotting = Plotting::findOrFail($id);
        $updated = $this->plottingService->perbaruiData($plotting, $request->validated());
        return new PlottingResource($updated);
    }

    public function destroy($id)
    {
        Plotting::findOrFail($id)->delete();
        return response()->json(['message' => 'Plotting berhasil dihapus.']);
    }

    public function myPlotting(Request $request)
    {
        // 1. Ambil String UUID Guru dari user yang sedang login via Sanctum
        $guruId = $request->user()->id;

        // 2. Lempar ke Service Layer
        $plottings = $this->plottingService->getPlottingByGuru($guruId);

        // 3. Kembalikan menggunakan API Resource Collection bawaan Laravel
        return PlottingResource::collection($plottings)
            ->additional([
                'status' => 'success',
                'message' => 'Data tugas mengajar berhasil dimuat.'
            ]);
    }
}

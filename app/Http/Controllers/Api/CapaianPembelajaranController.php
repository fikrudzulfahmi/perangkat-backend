<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CapaianPembelajaran;
use App\Http\Requests\CapaianPembelajaranRequest;
use App\Http\Resources\CapaianPembelajaranResource;
use App\Services\CapaianPembelajaranService;

class CapaianPembelajaranController extends Controller
{
    // 🟢 Cukup gunakan SATU properti service saja
    protected $cpService;

    // 🟢 Hanya boleh ada SATU constructor
    public function __construct(CapaianPembelajaranService $cpService)
    {
        $this->cpService = $cpService;
    }

    public function index(Request $request)
    {
        $data = $this->cpService->ambilPaginasiDanCari($request->query('search'), $request->query('mapel_id'));
        return CapaianPembelajaranResource::collection($data);
    }

    public function store(CapaianPembelajaranRequest $request)
    {
        return new CapaianPembelajaranResource($this->cpService->buatBaru($request->validated()));
    }

    public function show($id)
    {
        $cp = CapaianPembelajaran::with(['mapel', 'listTp'])->findOrFail($id);
        return new CapaianPembelajaranResource($cp);
    }

    public function update(CapaianPembelajaranRequest $request, $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        return new CapaianPembelajaranResource($this->cpService->perbaruiData($cp, $request->validated()));
    }

    public function destroy($id)
    {
        $this->cpService->hapusData(CapaianPembelajaran::findOrFail($id));
        return response()->json(['message' => 'Capaian Pembelajaran berhasil dihapus.']);
    }

    public function getStructureForGuru(Request $request)
    {
        $mapelId = $request->query('mapel_id');

        // 🟢 PERBAIKAN: Ubah menjadi $this->cpService agar sama dengan method lainnya
        $capaian = $this->cpService->getStructureByMapel($mapelId);

        return CapaianPembelajaranResource::collection($capaian);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // Wajib di-import karena sekarang beda folder
use App\Http\Requests\ModulAjarRequest;
use App\Http\Resources\ModulAjarResource;
use App\Models\ModulAjar;
use App\Services\ModulAjarService;
use Illuminate\Http\Request;

class ModulAjarController extends Controller
{
    protected $modulAjarService;

    public function __construct(ModulAjarService $modulAjarService)
    {
        $this->modulAjarService = $modulAjarService;
    }

    public function index(Request $request)
    {
        if ($request->has('tahun_ajaran_id') && $request->has('mapel_id')) {
            $mapelId = $request->query('mapel_id');
            $tahunAjaranId = $request->query('tahun_ajaran_id');

            // UBAH BARIS INI: sesuaikan dengan nama parameter dari frontend (guru_id)
            $guru_id = $request->query('guru_id');

            $modulReferensi = $this->modulAjarService->getReferensiClone($guru_id, $mapelId, $tahunAjaranId);

            return ModulAjarResource::collection($modulReferensi);
        }

        $plottingId = $request->query('plotting_id');
        $modulAjars = $this->modulAjarService->getPaginasi($plottingId);

        return ModulAjarResource::collection($modulAjars);
    }

    public function store(ModulAjarRequest $request)
    {
        $modulAjar = $this->modulAjarService->store($request->validated());

        return response()->json([
            'message' => 'Modul Ajar berhasil disimpan',
            'data' => new ModulAjarResource($modulAjar)
        ], 201);
    }

    public function show(ModulAjar $modulAjar)
    {
        $modulAjar->load(['tujuanPembelajarans', 'plotting']);
        return new ModulAjarResource($modulAjar);
    }

    public function update(ModulAjarRequest $request, ModulAjar $modulAjar)
    {
        $updatedModul = $this->modulAjarService->update($modulAjar, $request->validated());

        return response()->json([
            'message' => 'Modul Ajar berhasil diperbarui',
            'data' => new ModulAjarResource($updatedModul)
        ]);
    }

    public function destroy(ModulAjar $modulAjar)
    {
        $this->modulAjarService->delete($modulAjar);

        return response()->json(['message' => 'Modul Ajar berhasil dihapus']);
    }
}

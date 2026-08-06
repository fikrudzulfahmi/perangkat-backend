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

    /**
     * Rencana pertemuan dari Prosem untuk TP terpilih (posisi GLOBAL dalam
     * tahun ajaran) -- dipakai frontend untuk auto-isi field "Pertemuan" saat
     * guru mencentang TP, tanpa harus menunggu Generate AI.
     */
    public function rencanaPertemuan(Request $request)
    {
        $request->validate([
            'plotting_id' => 'required|uuid|exists:plottings,id',
            'tujuan_pembelajaran_id' => 'required|array|min:1',
            'tujuan_pembelajaran_id.*' => 'required|uuid|exists:tujuan_pembelajarans,id',
        ]);

        $planner = app(\App\Services\ProsemPlannerService::class);
        $hasil = $planner->buildRencanaPertemuan(
            $request->plotting_id,
            $request->tujuan_pembelajaran_id
        );

        if (empty($hasil['rencana'])) {
            return response()->json([
                'message' => 'Tidak ditemukan data Prosem untuk Tujuan Pembelajaran yang dipilih. Pastikan Prosem sudah diisi guru untuk TP ini.'
            ], 422);
        }

        return response()->json([
            'data' => $hasil['rencana'],
            'total_pertemuan' => $hasil['total_pertemuan'],
            'pertemuan_awal' => $hasil['pertemuan_awal_global'] ?? $hasil['pertemuan_awal'],
            'pertemuan_akhir' => $hasil['pertemuan_akhir_global'] ?? $hasil['pertemuan_akhir'],
            'pertemuan_label' => $hasil['pertemuan_label_global'] ?? '1',
            'jp_per_pertemuan' => $hasil['jp_per_pertemuan'],
        ]);
    }
}

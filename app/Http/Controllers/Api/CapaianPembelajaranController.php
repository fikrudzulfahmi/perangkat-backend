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
    protected $cpService;

    public function __construct(CapaianPembelajaranService $cpService)
    {
        $this->cpService = $cpService;
    }

    /**
     * Struktur CP + TP untuk guru (dipakai halaman Kurikulum guru, ATP,
     * Bank Soal, dan Cetak Perangkat). Guru hanya melihat CP dari mapel
     * yang di-plotting kepadanya.
     */
    public function getStructureForGuru(Request $request)
    {
        $guruId  = $request->user()->id;
        $mapelId = $request->query('mapel_id');

        $capaian = $this->cpService->getStructureByMapelUntukGuru($guruId, $mapelId);

        return CapaianPembelajaranResource::collection($capaian);
    }

    public function store(CapaianPembelajaranRequest $request)
    {
        $cp = $this->cpService->buatBaruUntukGuru($request->user()->id, $request->validated());

        return new CapaianPembelajaranResource($cp->load('mapel'));
    }

    public function show(Request $request, $id)
    {
        $cp = CapaianPembelajaran::with(['mapel', 'listTp'])->findOrFail($id);

        // Pastikan CP ini berasal dari mapel milik guru yang login
        $this->cpService->pastikanMapelMilikGuru($request->user()->id, $cp->mapel_id);

        return new CapaianPembelajaranResource($cp);
    }

    public function update(CapaianPembelajaranRequest $request, $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $updated = $this->cpService->perbaruiDataUntukGuru($request->user()->id, $cp, $request->validated());

        return new CapaianPembelajaranResource($updated->load('mapel'));
    }

    public function destroy(Request $request, $id)
    {
        $cp = CapaianPembelajaran::findOrFail($id);
        $this->cpService->hapusDataUntukGuru($request->user()->id, $cp);

        return response()->json(['message' => 'Capaian Pembelajaran berhasil dihapus.']);
    }
}

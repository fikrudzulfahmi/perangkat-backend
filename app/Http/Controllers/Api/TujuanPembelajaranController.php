<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TujuanPembelajaran;
use App\Http\Requests\TujuanPembelajaranRequest;
use App\Http\Resources\TujuanPembelajaranResource;
use App\Services\TujuanPembelajaranService;

class TujuanPembelajaranController extends Controller
{
    protected $tpService;

    public function __construct(TujuanPembelajaranService $tpService)
    {
        $this->tpService = $tpService;
    }

    public function store(TujuanPembelajaranRequest $request)
    {
        return new TujuanPembelajaranResource($this->tpService->buatBaru($request->validated()));
    }

    public function update(TujuanPembelajaranRequest $request, $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        return new TujuanPembelajaranResource($this->tpService->perbaruiData($tp, $request->validated()));
    }

    public function destroy($id)
    {
        $this->tpService->hapusData(TujuanPembelajaran::findOrFail($id));
        return response()->json(['message' => 'Tujuan Pembelajaran berhasil dihapus.']);
    }
}

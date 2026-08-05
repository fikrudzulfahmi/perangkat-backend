<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $tp = $this->tpService->buatBaruUntukGuru($request->user()->id, $request->validated());

        return new TujuanPembelajaranResource($tp);
    }

    public function update(TujuanPembelajaranRequest $request, $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $updated = $this->tpService->perbaruiDataUntukGuru($request->user()->id, $tp, $request->validated());

        return new TujuanPembelajaranResource($updated);
    }

    public function destroy(Request $request, $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $this->tpService->hapusDataUntukGuru($request->user()->id, $tp);

        return response()->json(['message' => 'Tujuan Pembelajaran berhasil dihapus.']);
    }
}

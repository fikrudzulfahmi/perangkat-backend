<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TahunPelajaran;
use App\Http\Requests\TahunPelajaranRequest;
use App\Http\Resources\TahunPelajaranResource;
use App\Services\TahunPelajaranService;

class TahunPelajaranController extends Controller
{
    protected $tahunService;

    public function __construct(TahunPelajaranService $tahunService)
    {
        $this->tahunService = $tahunService;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $data = $this->tahunService->ambilPaginasiDanCari($search);

        return TahunPelajaranResource::collection($data);
    }

    public function store(TahunPelajaranRequest $request)
    {
        $tahun = $this->tahunService->buatBaru($request->validated());
        return new TahunPelajaranResource($tahun);
    }

    public function update(TahunPelajaranRequest $request, $id)
    {
        $tahun = TahunPelajaran::findOrFail($id);
        $updated = $this->tahunService->perbaruiData($tahun, $request->validated());
        return new TahunPelajaranResource($updated);
    }

    public function destroy($id)
    {
        $tahun = TahunPelajaran::findOrFail($id);
        $this->tahunService->hapusData($tahun);
        return response()->json(['message' => 'Tahun Pelajaran berhasil dihapus.']);
    }
}

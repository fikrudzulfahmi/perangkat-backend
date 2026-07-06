<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Http\Requests\KelasRequest;
use App\Http\Resources\KelasResource;
use App\Services\KelasService;

class KelasController extends Controller
{
    protected $kelasService;

    public function __construct(KelasService $kelasService)
    {
        $this->kelasService = $kelasService;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $data = $this->kelasService->ambilPaginasiDanCari($search);

        return KelasResource::collection($data);
    }

    public function store(KelasRequest $request)
    {
        $kelas = $this->kelasService->buatBaru($request->validated());
        return new KelasResource($kelas);
    }

    public function show($id)
    {
        $kelas = Kelas::findOrFail($id);
        return new KelasResource($kelas);
    }

    public function update(KelasRequest $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $updated = $this->kelasService->perbaruiData($kelas, $request->validated());
        return new KelasResource($updated);
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $this->kelasService->hapusData($kelas);
        return response()->json(['message' => 'Data kelas berhasil dihapus.']);
    }
}

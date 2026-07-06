<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MataPelajaran;
use App\Http\Requests\MataPelajaranRequest;
use App\Http\Resources\MataPelajaranResource;
use App\Services\MataPelajaranService;

class MataPelajaranController extends Controller
{
    protected $mapelService;

    // Suntikkan Service Layer melalui Constructor Dependency Injection
    public function __construct(MataPelajaranService $mapelService)
    {
        $this->mapelService = $mapelService;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $data = $this->mapelService->ambilPaginasiDanCari($search);

        return MataPelajaranResource::collection($data);
    }

    public function store(MataPelajaranRequest $request)
    {
        $mapel = $this->mapelService->buatBaru($request->validated());
        return new MataPelajaranResource($mapel);
    }

    public function show($id)
    {
        $mapel = MataPelajaran::findOrFail($id);
        return new MataPelajaranResource($mapel);
    }

    public function update(MataPelajaranRequest $request, $id)
    {
        $mapel = MataPelajaran::findOrFail($id);
        $updated = $this->mapelService->perbaruiData($mapel, $request->validated());
        return new MataPelajaranResource($updated);
    }

    public function destroy($id)
    {
        $mapel = MataPelajaran::findOrFail($id);
        $this->mapelService->hapusData($mapel);
        return response()->json(['message' => 'Mata pelajaran berhasil dihapus.']);
    }
}

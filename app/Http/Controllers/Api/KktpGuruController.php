<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// Import di bawah ini sekarang mengarah langsung ke base folder sesuai request Agan
use App\Http\Requests\GetKktpRequest;
use App\Http\Requests\SaveKktpRequest;
use App\Http\Resources\KktpStructureResource;
use App\Services\KktpService;
use Illuminate\Http\JsonResponse;

class KktpGuruController extends Controller
{
    protected KktpService $kktpService;

    // Inject Service
    public function __construct(KktpService $kktpService)
    {
        $this->kktpService = $kktpService;
    }

    public function getKktp(GetKktpRequest $request): KktpStructureResource
    {
        $data = $this->kktpService->getKktpStructure(
            $request->validated('mapel_id'),
            $request->validated('kelas_id')
        );

        return new KktpStructureResource($data);
    }

    public function saveKktp(SaveKktpRequest $request): JsonResponse
    {
        $this->kktpService->saveKktpData(
            $request->validated('kelas_id'),
            $request->validated('items')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kriteria Ketercapaian TP (KKTP) berhasil disimpan!'
        ]);
    }
}

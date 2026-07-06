<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetProsemRequest;
use App\Http\Requests\SaveProsemRequest;
use App\Http\Resources\ProsemStructureResource; // <-- Jangan lupa import ini
use App\Services\ProsemService;
use Illuminate\Http\JsonResponse;

class ProsemGuruController extends Controller
{
    protected ProsemService $prosemService;

    public function __construct(ProsemService $prosemService)
    {
        $this->prosemService = $prosemService;
    }

    // Ubah return type-nya menjadi Resource
    public function getProsem(GetProsemRequest $request): ProsemStructureResource
    {
        $data = $this->prosemService->getProsemStructure($request->validated('plotting_id'));

        // Gunakan Resource untuk merapikan response JSON
        return new ProsemStructureResource($data);
    }

    public function saveProsem(SaveProsemRequest $request): JsonResponse
    {
        try {
            $this->prosemService->saveProsemData(
                $request->validated('plotting_id'),
                $request->validated('items')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data Program Semester (Prosem) berhasil disimpan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}

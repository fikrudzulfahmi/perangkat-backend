<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DokumenStatisRequest;
use App\Http\Resources\DokumenStatisResource;
use App\Services\DokumenStatisService;
use Illuminate\Http\Request;

class DokumenStatisController extends Controller
{
    protected DokumenStatisService $dokumenStatisService;

    public function __construct(DokumenStatisService $dokumenStatisService)
    {
        $this->dokumenStatisService = $dokumenStatisService;
    }

    public function index(Request $request)
    {
        $jenis = $request->query('jenis');

        $dokumen = $this->dokumenStatisService->getByJenis($jenis);

        if (!$dokumen) {
            return response()->json([
                'data' => null
            ]);
        }

        return new DokumenStatisResource($dokumen);
    }

    public function store(DokumenStatisRequest $request)
    {
        $dokumen = $this->dokumenStatisService->updateOrCreateDokumen($request->validated());

        return (new DokumenStatisResource($dokumen))
            ->additional(['message' => 'Dokumen statis berhasil diperbarui.']);
    }
}

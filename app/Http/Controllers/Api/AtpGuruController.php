<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SaveAtpRequest;
use App\Http\Resources\AtpGuruResource;
use App\Services\AtpGuruService;

class AtpGuruController extends Controller
{
    protected $atpGuruService;

    // Inject Service Layer melalui Constructor
    public function __construct(AtpGuruService $atpGuruService)
    {
        $this->atpGuruService = $atpGuruService;
    }

    // Ambil Data ATP Guru
    public function getAtp(Request $request)
    {
        $guruId  = $request->user()->id;
        $mapelId = $request->query('mapel_id');
        $kelasId = $request->query('kelas_id');

        $data = $this->atpGuruService->getAtpByGuru($guruId, $mapelId, $kelasId);

        return AtpGuruResource::collection($data);
    }

    // Simpan Data ATP Guru via Form Request
    public function saveAtp(SaveAtpRequest $request)
    {
        $guruId = $request->user()->id;

        $this->atpGuruService->simpanMassalAtp($guruId, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Seluruh struktur ATP berhasil disimpan!'
        ]);
    }
}

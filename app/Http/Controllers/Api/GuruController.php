<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGuruRequest;
use App\Http\Requests\UpdateGuruRequest;
use App\Http\Resources\GuruResource;
use App\Services\GuruService;

class GuruController extends Controller
{
    protected $guruService;

    // Inject Service melalui Constructor
    public function __construct(GuruService $guruService)
    {
        $this->guruService = $guruService;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        
        $dataGuru = $this->guruService->getGuruPaginated($search, $perPage);

        return GuruResource::collection($dataGuru);
    }

    public function store(StoreGuruRequest $request)
    {
        // $request->validated() memastikan hanya data lolos sensor validasi yang masuk
        $guru = $this->guruService->storeGuru($request->validated());

        return (new GuruResource($guru))
            ->additional([
                'status' => 'success',
                'message' => 'Data Guru berhasil ditambahkan!'
            ]);
    }

    public function update(UpdateGuruRequest $request, $id)
    {
        // $request->validated() berisi data yang sudah tervalidasi
        $guru = $this->guruService->updateGuru($id, $request->validated());

        return (new GuruResource($guru))->additional([
            'status' => 'success',
            'message' => 'Data Guru berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->guruService->deleteGuru($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru berhasil dihapus!'
        ]);
    }
}

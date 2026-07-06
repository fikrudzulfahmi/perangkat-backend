<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalMengajarRequest;
use App\Http\Resources\JadwalMengajarResource;
use App\Models\JadwalMengajar;
use App\Services\JadwalMengajarService;
use Illuminate\Http\Request;

class JadwalMengajarController extends Controller
{
    protected JadwalMengajarService $jadwalService;

    public function __construct(JadwalMengajarService $jadwalService)
    {
        $this->jadwalService = $jadwalService;
    }

    public function index(Request $request)
    {
        // Ambil filter dari query string (misal: ?tahun_pelajaran_id=1)
        $filters = $request->only(['tahun_pelajaran_id', 'kelas_id', 'guru_id']);

        $jadwal = $this->jadwalService->getAll($filters);

        return JadwalMengajarResource::collection($jadwal);
    }

    public function store(JadwalMengajarRequest $request)
    {
        $jadwal = $this->jadwalService->create($request->validated());

        return (new JadwalMengajarResource($jadwal))
            ->additional(['message' => 'Jadwal mengajar berhasil ditambahkan.']);
    }

    public function show(JadwalMengajar $jadwal_mengajar)
    {
        return new JadwalMengajarResource($jadwal_mengajar);
    }

    public function update(JadwalMengajarRequest $request, JadwalMengajar $jadwal_mengajar)
    {
        $jadwal = $this->jadwalService->update($jadwal_mengajar, $request->validated());

        return (new JadwalMengajarResource($jadwal))
            ->additional(['message' => 'Jadwal mengajar berhasil diperbarui.']);
    }

    public function destroy(JadwalMengajar $jadwal_mengajar)
    {
        $this->jadwalService->delete($jadwal_mengajar);

        return response()->json(['message' => 'Jadwal mengajar berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SiswaActionRequest;
use App\Http\Resources\SiswaResource;
use App\Services\SiswaService;

class SiswaController extends Controller
{
    protected $siswaService;

    public function __construct(SiswaService $siswaService)
    {
        $this->siswaService = $siswaService;
    }

    public function index(Request $request)
    {
        $kelasId = $request->query('kelas_id');
        $tahunPelajaranId = $request->query('tahun_pelajaran_id');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $dataSiswa = $this->siswaService->ambilPaginasi($perPage, $kelasId, $tahunPelajaranId, $search);

        // Mengembalikan data menggunakan Resource
        return SiswaResource::collection($dataSiswa);
    }

    public function store(SiswaActionRequest $request)
    {
        // Logikanya tetap sama persis, hanya nama fungsinya yang berubah menjadi standar resource
        $sukses = $this->siswaService->tarikSiswaDariExternal($request->validated());

        if ($sukses) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data siswa berhasil disinkronkan!'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menarik data dari aplikasi induk.'
        ], 500);
    }

    public function bulkDelete(SiswaActionRequest $request)
    {
        $this->siswaService->bulkDeleteSiswa($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Semua data siswa di kelas ini berhasil dihapus!'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KalenderEfektifRequest;
use App\Http\Resources\KalenderEfektifResource;
use App\Models\KalenderEfektif;
use App\Services\KalenderEfektifService;
use Illuminate\Http\Request;

class KalenderEfektifController extends Controller
{
    protected $kalenderService;

    // Inject Service ke dalam Controller via Constructor
    public function __construct(KalenderEfektifService $kalenderService)
    {
        $this->kalenderService = $kalenderService;
    }

    /**
     * Menampilkan data rincian kalender berdasarkan Tahun Pelajaran
     */
    public function index(Request $request)
    {
        $request->validate([
            'tahun_pelajaran_id' => 'required|uuid|exists:tahun_pelajarans,id'
        ]);

        $data = KalenderEfektif::where('tahun_pelajaran_id', $request->tahun_pelajaran_id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Mengembalikan kumpulan data dengan balutan API Resource
        return KalenderEfektifResource::collection($data);
    }

    /**
     * Menyimpan atau memperbarui data kalender akademik secara massal
     */
    public function store(KalenderEfektifRequest $request)
    {
        // Jalankan logika penyimpanan dari layer Service
        $sukses = $this->kalenderService->simpanKalenderMassal($request->validated());

        if ($sukses) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Rincian kalender akademik berhasil disimpan!'
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Gagal menyimpan data kalender akademik.'
        ], 500);
    }
}

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

    public function referensiTeman(Request $request)
    {
        $plottingIdKita = $request->query('plotting_id');
        $tahunPelajaranId = $request->query('tahun_pelajaran_id');

        // Cari tahu mapel_id dari plotting kita saat ini
        $plottingKita = \App\Models\Plotting::find($plottingIdKita);

        if (!$plottingKita) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Cari plotting milik orang lain dengan mapel_id yang sama
        // CATATAN: Pastikan relasi 'guru' dan 'kelas' sesuai dengan nama di Model Plotting Anda
        $referensi = \App\Models\Plotting::with(['guru', 'list_kelas'])
            ->where('mapel_id', $plottingKita->mapel_id)
            ->where('tahun_pelajaran_id', $tahunPelajaranId)
            ->where('id', '!=', $plottingIdKita)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $referensi
        ]);
    }

    public function ambilAtpTeman(Request $request)
    {
        $plottingIdTeman = $request->query('plotting_id_teman');

        // Ambil susunan ATP berdasarkan plotting teman yang dipilih
        $atpTeman = \App\Models\Atp::where('plotting_id', $plottingIdTeman)->get();

        return response()->json([
            'status' => 'success',
            'data' => $atpTeman
        ]);
    }
}

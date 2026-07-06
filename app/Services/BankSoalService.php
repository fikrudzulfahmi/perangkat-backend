<?php

namespace App\Services;

use App\Models\BankSoal;
use Illuminate\Auth\Access\AuthorizationException;

class BankSoalService
{
    public function getSoalByGuru($guruId, $plottingId = null)
    {
        $query = BankSoal::with(['tujuanPembelajaran'])
            ->whereHas('plotting', function ($q) use ($guruId) {
                $q->where('guru_id', $guruId);
            });

        // Filter tambahan jika guru memilih mapel tertentu di Vue
        if ($plottingId) {
            $query->where('plotting_id', $plottingId);
        }

        return $query->latest()->get();
    }

    public function createSoal(array $data)
    {
        return BankSoal::create($data);
    }

    public function updateSoal($id, $guruId, array $data)
    {
        $soal = BankSoal::findOrFail($id);

        if ($soal->plotting->guru_id !== $guruId) {
            throw new AuthorizationException('Anda tidak berhak mengubah soal ini.');
        }

        $soal->update($data);
        return $soal->load('tujuanPembelajaran');
    }

    public function deleteSoal($id, $guruId)
    {
        $soal = BankSoal::findOrFail($id);

        if ($soal->plotting->guru_id !== $guruId) {
            throw new AuthorizationException('Anda tidak berhak menghapus soal ini.');
        }

        return $soal->delete();
    }
}

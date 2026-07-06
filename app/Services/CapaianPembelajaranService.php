<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;

class CapaianPembelajaranService
{
    public function ambilPaginasiDanCari($search, $mapelId = null, $perPage = 10)
    {
        $query = CapaianPembelajaran::with('mapel');

        if ($mapelId) $query->where('mapel_id', $mapelId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('elemen', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('fase', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function buatBaru(array $data)
    {
        return CapaianPembelajaran::create($data);
    }
    public function perbaruiData(CapaianPembelajaran $cp, array $data)
    {
        $cp->update($data);
        return $cp;
    }
    public function hapusData(CapaianPembelajaran $cp)
    {
        return $cp->delete();
    }
    public function getStructureByMapel($mapelId)
    {
        // 🟢 PERBAIKAN: Ubah 'mata_pelajaran_id' menjadi 'mapel_id' 
        // (Atau sesuaikan dengan nama kolom relasi mapel yang benar di tabel capaian_pembelajarans Anda)
        return \App\Models\CapaianPembelajaran::with(['listTp']) // Pastikan nama relasinya listTp (sesuai dengan yang ada di show() controller)
            ->where('mapel_id', $mapelId)
            ->get();
    }
}

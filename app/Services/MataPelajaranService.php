<?php

namespace App\Services;

use App\Models\MataPelajaran;
use App\Models\Plotting;

class MataPelajaranService
{
    /**
     * Ambil daftar mapel yang di-plotting ke seorang guru (tanpa paginasi,
     * urut alfabetis). Dipakai dropdown "CP & TP" dan halaman-halaman guru.
     */
    public function ambilMapelGuru(string $guruId)
    {
        $mapelIds = Plotting::where('guru_id', $guruId)
            ->whereHas('tahunPelajaran', fn ($q) => $q->where('is_active', 1))
            ->pluck('mapel_id')
            ->unique()
            ->values();

        return MataPelajaran::whereIn('id', $mapelIds)
            ->orderBy('nama_mapel', 'asc')
            ->get();
    }

    public function ambilPaginasiDanCari($search, $perPage = 20)
    {
        $query = MataPelajaran::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_mapel', 'like', "%{$search}%")
                    ->orWhere('kode_mapel', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nama_mapel', 'asc')->paginate($perPage);
    }

    public function buatBaru(array $data)
    {
        return MataPelajaran::create($data);
    }

    public function perbaruiData(MataPelajaran $mapel, array $data)
    {
        $mapel->update($data);
        return $mapel;
    }

    public function hapusData(MataPelajaran $mapel)
    {
        return $mapel->delete();
    }
}

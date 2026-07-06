<?php

namespace App\Services;

use App\Models\MataPelajaran;

class MataPelajaranService
{
    public function ambilPaginasiDanCari($search, $perPage = 10)
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

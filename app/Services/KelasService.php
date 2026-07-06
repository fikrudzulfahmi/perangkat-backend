<?php

namespace App\Services;

use App\Models\Kelas;

class KelasService
{
    public function ambilPaginasiDanCari($search, $perPage = 10)
    {
        $query = Kelas::query();

        if ($search) {
            $query->where('nama_kelas', 'like', "%{$search}%");
        }

        return $query->orderBy('nama_kelas', 'asc')->paginate($perPage);
    }

    public function buatBaru(array $data)
    {
        return Kelas::create($data);
    }

    public function perbaruiData(Kelas $kelas, array $data)
    {
        $kelas->update($data);
        return $kelas;
    }

    public function hapusData(Kelas $kelas)
    {
        return $kelas->delete();
    }
}

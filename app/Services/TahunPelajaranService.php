<?php

namespace App\Services;

use App\Models\TahunPelajaran;

class TahunPelajaranService
{
    public function ambilPaginasiDanCari($search, $perPage = 10)
    {
        $query = TahunPelajaran::query();

        if ($search) {
            $query->where('nama_tahun', 'like', "%{$search}%");
        }

        // Urutkan dari yang terbaru (descending)
        return $query->orderBy('nama_tahun', 'desc')->paginate($perPage);
    }

    public function buatBaru(array $data)
    {


        $this->cekDanResetStatusAktif($data['is_active']);
        return TahunPelajaran::create($data);
    }

    public function perbaruiData(TahunPelajaran $tahun, array $data)
    {


        // Jika status diubah menjadi aktif, matikan yang lain
        if ($data['is_active'] && !$tahun->is_active) {
            $this->cekDanResetStatusAktif(true);
        }

        $tahun->update($data);
        return $tahun;
    }

    public function hapusData(TahunPelajaran $tahun)
    {
        return $tahun->delete();
    }

    private function cekDanResetStatusAktif($isActive)
    {
        if ($isActive) {
            TahunPelajaran::where('is_active', true)->update(['is_active' => false]);
        }
    }

    public function ambilSemua()
    {
        // Menggunakan get() untuk menarik semua data tanpa batasan halaman/paginasi
        return TahunPelajaran::latest()->get();
    }
}

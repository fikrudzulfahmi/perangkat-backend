<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Support\Facades\Http;

class SiswaService
{
    public function ambilPaginasi($perPage = 10, $kelasId = null, $tahunPelajaranId = null, $search = null)
    {
        $query = Siswa::with(['kelas']);

        if ($tahunPelajaranId) {
            $query->where('tahun_pelajaran_id', $tahunPelajaranId);
        }

        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nama_siswa', 'asc')->paginate($perPage);
    }

    public function tarikSiswaDariExternal(array $data)
    {
        $kelas = Kelas::find($data['kelas_id']);
        if (!$kelas) return false;

        // 1. URL asli sesuai aplikasi induk
        $urlAplikasiSebelah = "https://induk.ingintau.my.id/siswa/apiSiswaByRombel";

        // 2. Tembak API dengan menyisipkan Header rahasia dan Parameter rombel
        $response = Http::withHeaders([
            'X-API-KEY' => 'TUsmekisa1968'
        ])->get($urlAplikasiSebelah, [
            'rombel' => $kelas->nama_kelas
        ]);

        // Jika API Key salah (401), parameter kurang (400), atau server mati (500), proses akan dihentikan di sini
        if ($response->failed()) return false;

        $dataSiswaExternal = $response->json();

        // 3. Looping data array JSON murni dari aplikasi induk
        foreach ($dataSiswaExternal as $siswaExt) {
            Siswa::updateOrCreate(
                [
                    'nisn' => $siswaExt['nisn'],
                    'tahun_pelajaran_id' => $data['tahun_pelajaran_id']
                ],
                [
                    'nis' => $siswaExt['nis'],
                    'nama_siswa' => $siswaExt['nama'],
                    'kelas_id' => $data['kelas_id'],
                ]
            );
        }

        return true;
    }

    public function bulkDeleteSiswa(array $data)
    {
        return Siswa::where('kelas_id', $data['kelas_id'])
            ->where('tahun_pelajaran_id', $data['tahun_pelajaran_id'])
            ->delete();
    }
}

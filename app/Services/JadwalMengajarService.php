<?php

namespace App\Services;

use App\Models\JadwalMengajar;

class JadwalMengajarService
{
    public function getAll(array $filters)
    {
        $query = JadwalMengajar::with(['tahunPelajaran', 'guru', 'mataPelajaran', 'kelas']);

        // Filter berdasarkan Tahun Pelajaran (Wajib/Sangat Direkomendasikan)
        if (!empty($filters['tahun_pelajaran_id'])) {
            $query->where('tahun_pelajaran_id', $filters['tahun_pelajaran_id']);
        }

        // Filter opsional jika ingin melihat jadwal per kelas atau per guru
        if (!empty($filters['kelas_id'])) {
            $query->where('kelas_id', $filters['kelas_id']);
        }
        if (!empty($filters['guru_id'])) {
            $query->where('guru_id', $filters['guru_id']);
        }

        return $query->orderBy('hari')->orderBy('jam_ke')->get();
    }

    public function create(array $data): JadwalMengajar
    {
        return JadwalMengajar::create($data);
    }

    public function update(JadwalMengajar $jadwal, array $data): JadwalMengajar
    {
        $jadwal->update($data);
        return $jadwal;
    }

    public function delete(JadwalMengajar $jadwal): bool
    {
        return $jadwal->delete();
    }
}

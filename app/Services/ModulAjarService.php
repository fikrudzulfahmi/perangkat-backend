<?php

namespace App\Services;

use App\Models\ModulAjar;
use App\Models\CapaianPembelajaran;
use Illuminate\Support\Facades\DB;

class ModulAjarService
{
    /**
     * Susun teks Capaian Pembelajaran otomatis dari CP induk TP yang dipilih
     * (rantai KSP: TP -> CP). Format: "Elemen: deskripsi CP" per baris.
     */
    private function susunCapaianPembelajaran(array $tujuanPembelajaranIds): ?string
    {
        $ids = array_values(array_filter($tujuanPembelajaranIds));
        if (empty($ids)) {
            return null;
        }

        $cpList = CapaianPembelajaran::whereHas('listTp', function ($q) use ($ids) {
            $q->whereIn('id', $ids);
        })->get();

        if ($cpList->isEmpty()) {
            return null;
        }

        return $cpList
            ->unique('id')
            ->map(fn ($cp) => ($cp->elemen ? "{$cp->elemen}: " : '') . ($cp->deskripsi ?? ''))
            ->implode("\n\n");
    }

    public function getPaginasi($plottingId = null, $perPage = 10)
    {
        // HAPUS 'bankSoals' dari eager loading
        $query = ModulAjar::with(['tujuanPembelajarans', 'plotting']);

        if ($plottingId) {
            $query->where('plotting_id', $plottingId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getReferensiClone($guru_id, $mapelId, $tahunAjaranId)
    {
        return ModulAjar::with(['tujuanPembelajarans'])
            ->whereHas('plotting', function ($query) use ($guru_id, $mapelId, $tahunAjaranId) {
                $query->where('mapel_id', $mapelId)
                    ->where('tahun_pelajaran_id', $tahunAjaranId)
                    ->where('guru_id', '!=', $guru_id);
            })
            ->latest()
            ->get(); // Menggunakan get() tanpa paginasi agar muncul semua di dropdown modal
    }

    public function store(array $data)
    {
        // Gunakan DB Transaction agar aman jika insert pivot gagal
        return DB::transaction(function () use ($data) {
            // Capaian Pembelajaran diisi OTOMATIS dari CP induk TP terpilih (Juknis KSP)
            if (isset($data['tujuan_pembelajaran_ids'])) {
                $data['capaian_pembelajaran'] = $this->susunCapaianPembelajaran($data['tujuan_pembelajaran_ids']);
            }

            $modulAjar = ModulAjar::create($data);

            if (!empty($data['tujuan_pembelajaran_ids'])) {
                $modulAjar->tujuanPembelajarans()->sync($data['tujuan_pembelajaran_ids']);
            }

            // BLOK KODE bank_soal_ids SUDAH DIHAPUS

            // HAPUS 'bankSoals' dari load relasi
            return $modulAjar->load(['tujuanPembelajarans']);
        });
    }

    public function update(ModulAjar $modulAjar, array $data)
    {
        return DB::transaction(function () use ($modulAjar, $data) {
            // Capaian Pembelajaran diisi OTOMATIS dari CP induk TP terpilih (Juknis KSP)
            if (isset($data['tujuan_pembelajaran_ids'])) {
                $data['capaian_pembelajaran'] = $this->susunCapaianPembelajaran($data['tujuan_pembelajaran_ids']);
            }

            $modulAjar->update($data);

            // Sync akan otomatis menambah yang baru dan menghapus yang tidak ada di array
            if (isset($data['tujuan_pembelajaran_ids'])) {
                $modulAjar->tujuanPembelajarans()->sync($data['tujuan_pembelajaran_ids']);
            }

            // BLOK KODE bank_soal_ids SUDAH DIHAPUS

            // HAPUS 'bankSoals' dari load relasi
            return $modulAjar->load(['tujuanPembelajarans']);
        });
    }
    public function delete(ModulAjar $modulAjar)
    {
        return DB::transaction(function () use ($modulAjar) {
            // Lepas relasi pivot dulu agar tidak menyisakan data orphan
            $modulAjar->tujuanPembelajarans()->detach();

            return $modulAjar->delete();
        });
    }
}

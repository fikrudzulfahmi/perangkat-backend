<?php

namespace App\Services;

use App\Models\ModulAjar;
use Illuminate\Support\Facades\DB;

class ModulAjarService
{
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
            // Data asesmen baru (asesmen_diagnostik, dll) akan otomatis tersimpan di sini
            // karena sudah dimasukkan ke $fillable di Model ModulAjar
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
            // Data asesmen baru akan otomatis ter-update di baris ini
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

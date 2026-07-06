<?php

namespace App\Services;

use App\Models\ModulAjar;
use Illuminate\Support\Facades\DB;

class ModulAjarService
{
    public function getPaginasi($plottingId = null, $perPage = 10)
    {
        $query = ModulAjar::with(['tujuanPembelajarans', 'bankSoals', 'plotting']);

        if ($plottingId) {
            $query->where('plotting_id', $plottingId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data)
    {
        // Gunakan DB Transaction agar aman jika insert pivot gagal
        return DB::transaction(function () use ($data) {
            $modulAjar = ModulAjar::create($data);

            if (!empty($data['tujuan_pembelajaran_ids'])) {
                $modulAjar->tujuanPembelajarans()->sync($data['tujuan_pembelajaran_ids']);
            }

            if (!empty($data['bank_soal_ids'])) {
                $modulAjar->bankSoals()->sync($data['bank_soal_ids']);
            }

            return $modulAjar->load(['tujuanPembelajarans', 'bankSoals']);
        });
    }

    public function update(ModulAjar $modulAjar, array $data)
    {
        return DB::transaction(function () use ($modulAjar, $data) {
            $modulAjar->update($data);

            // Sync akan otomatis menambah yang baru dan menghapus yang tidak ada di array
            if (isset($data['tujuan_pembelajaran_ids'])) {
                $modulAjar->tujuanPembelajarans()->sync($data['tujuan_pembelajaran_ids']);
            }

            if (isset($data['bank_soal_ids'])) {
                $modulAjar->bankSoals()->sync($data['bank_soal_ids']);
            }

            return $modulAjar->load(['tujuanPembelajarans', 'bankSoals']);
        });
    }

    public function delete(ModulAjar $modulAjar)
    {
        // Pivot table akan otomatis terhapus karena 'cascadeOnDelete' di migration
        return $modulAjar->delete();
    }
}

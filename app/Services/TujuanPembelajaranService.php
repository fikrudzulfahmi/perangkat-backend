<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;
use App\Models\TujuanPembelajaran;

class TujuanPembelajaranService
{
    /**
     * Pastikan CP induk dari TP ini berasal dari mapel milik guru.
     * Dipakai saat membuat TP baru (via capaian_pembelajaran_id).
     */
    public function pastikanCpMilikGuru(string $guruId, string $capaianPembelajaranId): void
    {
        $cp = CapaianPembelajaran::findOrFail($capaianPembelajaranId);

        app(CapaianPembelajaranService::class)
            ->pastikanMapelMilikGuru($guruId, $cp->mapel_id);
    }

    /**
     * Pastikan TP yang akan diubah/dihapus berasal dari CP milik guru.
     */
    public function pastikanTpMilikGuru(string $guruId, TujuanPembelajaran $tp): void
    {
        $cp = CapaianPembelajaran::findOrFail($tp->capaian_pembelajaran_id);

        app(CapaianPembelajaranService::class)
            ->pastikanMapelMilikGuru($guruId, $cp->mapel_id);
    }

    public function buatBaruUntukGuru(string $guruId, array $data)
    {
        $this->pastikanCpMilikGuru($guruId, $data['capaian_pembelajaran_id']);

        return TujuanPembelajaran::create($data);
    }

    public function perbaruiDataUntukGuru(string $guruId, TujuanPembelajaran $tp, array $data)
    {
        $this->pastikanTpMilikGuru($guruId, $tp);

        // Jika TP dipindah ke CP lain, CP baru juga harus milik guru.
        if (isset($data['capaian_pembelajaran_id']) && $data['capaian_pembelajaran_id'] !== $tp->capaian_pembelajaran_id) {
            $this->pastikanCpMilikGuru($guruId, $data['capaian_pembelajaran_id']);
        }

        $tp->update($data);

        return $tp;
    }

    public function hapusDataUntukGuru(string $guruId, TujuanPembelajaran $tp)
    {
        $this->pastikanTpMilikGuru($guruId, $tp);

        return $tp->delete();
    }
}

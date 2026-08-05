<?php

namespace App\Services;

use App\Models\CapaianPembelajaran;
use App\Models\Plotting;

class CapaianPembelajaranService
{
    /**
     * Ambil daftar ID mapel yang di-plotting ke seorang guru
     * (hanya dari tahun pelajaran yang aktif).
     */
    public function getMapelIdsGuru(string $guruId): array
    {
        return Plotting::where('guru_id', $guruId)
            ->whereHas('tahunPelajaran', fn ($q) => $q->where('is_active', 1))
            ->pluck('mapel_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Pastikan mapel yang diakses benar-benar di-plotting ke guru tersebut.
     * Kalau bukan, langsung tolak dengan 403.
     */
    public function pastikanMapelMilikGuru(string $guruId, string $mapelId): void
    {
        if (!in_array($mapelId, $this->getMapelIdsGuru($guruId), true)) {
            abort(403, 'Anda hanya dapat mengelola CP/TP untuk mata pelajaran yang di-plotting kepada Anda.');
        }
    }

    /**
     * Ambil struktur CP + TP untuk guru.
     * - Jika $mapelId dikirim: hanya CP mapel itu, dengan cek kepemilikan.
     * - Jika $mapelId kosong: semua CP dari semua mapel milik guru.
     */
    public function getStructureByMapelUntukGuru(string $guruId, ?string $mapelId = null)
    {
        $query = CapaianPembelajaran::with(['listTp' => function ($q) {
            $q->orderBy('kode_tp', 'asc');
        }]);

        if ($mapelId) {
            $this->pastikanMapelMilikGuru($guruId, $mapelId);
            $query->where('mapel_id', $mapelId);
        } else {
            $query->whereIn('mapel_id', $this->getMapelIdsGuru($guruId));
        }

        return $query->get();
    }

    public function buatBaruUntukGuru(string $guruId, array $data)
    {
        $this->pastikanMapelMilikGuru($guruId, $data['mapel_id']);

        return CapaianPembelajaran::create($data);
    }

    public function perbaruiDataUntukGuru(string $guruId, CapaianPembelajaran $cp, array $data)
    {
        // Guru harus punya akses ke mapel CP yang lama...
        $this->pastikanMapelMilikGuru($guruId, $cp->mapel_id);

        // ...dan jika mapel-nya dipindah, mapel baru juga harus miliknya.
        if (isset($data['mapel_id']) && $data['mapel_id'] !== $cp->mapel_id) {
            $this->pastikanMapelMilikGuru($guruId, $data['mapel_id']);
        }

        $cp->update($data);

        return $cp;
    }

    public function hapusDataUntukGuru(string $guruId, CapaianPembelajaran $cp)
    {
        $this->pastikanMapelMilikGuru($guruId, $cp->mapel_id);

        return $cp->delete();
    }
}

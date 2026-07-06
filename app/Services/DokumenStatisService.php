<?php

namespace App\Services;

use App\Models\DokumenStatis;

class DokumenStatisService
{
    public function getByJenis(string $jenis): ?DokumenStatis
    {
        return DokumenStatis::where('jenis_dokumen', $jenis)->first();
    }

    public function updateOrCreateDokumen(array $data): DokumenStatis
    {
        return DokumenStatis::updateOrCreate(
            ['jenis_dokumen' => $data['jenis']],
            ['isi_dokumen'   => $data['isi']]
        );
    }
}

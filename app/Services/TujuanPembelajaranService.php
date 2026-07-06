<?php

namespace App\Services;

use App\Models\TujuanPembelajaran;

class TujuanPembelajaranService
{
    public function buatBaru(array $data)
    {
        return TujuanPembelajaran::create($data);
    }
    public function perbaruiData(TujuanPembelajaran $tp, array $data)
    {
        $tp->update($data);
        return $tp;
    }
    public function hapusData(TujuanPembelajaran $tp)
    {
        return $tp->delete();
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MataPelajaranResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id, // Mengembalikan string UUID ke Vue
            'kode_mapel' => $this->kode_mapel,
            'nama_mapel' => $this->nama_mapel,
        ];
    }
}

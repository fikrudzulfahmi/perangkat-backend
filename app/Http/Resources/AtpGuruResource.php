<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AtpGuruResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'guru_id'                => $this->guru_id,
            'mapel_id'               => $this->mapel_id,

            // 🟢 PERUBAHAN DI SINI:
            // Frontend taunya nama variabel ini adalah 'kelas_id', 
            // tapi datanya kita ambil dari kolom 'plotting_id' di database
            'kelas_id'               => $this->plotting_id,

            'tujuan_pembelajaran_id' => $this->tujuan_pembelajaran_id,
            'semester'               => $this->semester,
            'nomor_urut'             => $this->nomor_urut,
            'alokasi_jp'             => $this->alokasi_jp,
        ];
    }
}

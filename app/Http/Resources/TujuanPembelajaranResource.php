<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TujuanPembelajaranResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                      => $this->id,
            'capaian_pembelajaran_id' => $this->capaian_pembelajaran_id,
            'kode_tp'                 => $this->kode_tp,
            'deskripsi'               => $this->deskripsi,
            'created_at'              => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

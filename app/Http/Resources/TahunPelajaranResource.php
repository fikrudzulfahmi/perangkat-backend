<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TahunPelajaranResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'nama_tahun' => $this->nama_tahun,
            'is_active'  => $this->is_active,
        ];
    }
}

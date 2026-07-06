<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id, // String UUID
            'nama_kelas' => $this->nama_kelas,
        ];
    }
}

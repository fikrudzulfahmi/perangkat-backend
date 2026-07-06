<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CapaianPembelajaranResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'mapel_id'   => $this->mapel_id,
            'nama_mapel' => $this->mapel ? $this->mapel->nama_mapel : null,
            'fase'       => $this->fase,
            'elemen'     => $this->elemen,
            'deskripsi'  => $this->deskripsi,
            'list_tp'    => TujuanPembelajaranResource::collection($this->whenLoaded('listTp')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

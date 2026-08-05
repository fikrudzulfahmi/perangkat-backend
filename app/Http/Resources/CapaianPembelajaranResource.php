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
            'kompetensi' => $this->kompetensi,
            'konten_materi' => $this->konten_materi,
            'bentuk_pemahaman' => $this->bentuk_pemahaman,
            'list_tp'    => TujuanPembelajaranResource::collection($this->whenLoaded('listTp')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

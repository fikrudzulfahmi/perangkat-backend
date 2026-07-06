<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuPeganganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'plotting_id'  => $this->plotting_id,
            // Opsional: Langsung inject nama mapel jika relasi 'plotting.mapel' di-load
            'nama_mapel'   => $this->whenLoaded('plotting', function () {
                return $this->plotting->mapel->nama_mapel ?? null;
            }),
            'judul_buku'   => $this->judul_buku,
            'penulis'      => $this->penulis,
            'penerbit'     => $this->penerbit,
            'tahun_terbit' => $this->tahun_terbit,
            'jenis_buku'   => $this->jenis_buku,
            'created_at'   => $this->created_at,
        ];
    }
}

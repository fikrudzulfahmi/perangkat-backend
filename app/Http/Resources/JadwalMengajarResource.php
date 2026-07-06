<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalMengajarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blok' => $this->blok,
            'hari' => $this->hari,
            'jam_ke' => $this->jam_ke,
            'tahun_pelajaran' => [
                'id' => $this->tahun_pelajaran_id,
                'nama' => $this->tahunPelajaran?->tahun_pelajaran ?? $this->tahunPelajaran?->nama, // antisipasi nama field tahun
            ],
            'guru' => [
                'id' => $this->guru_id,
                'name' => $this->guru?->name,
            ],
            'mapel' => [
                'id' => $this->mata_pelajaran_id,
                'nama' => $this->mataPelajaran?->nama ?? $this->mataPelajaran?->nama_mapel,
            ],
            'kelas' => [
                'id' => $this->kelas_id,
                'nama' => $this->kelas?->nama ?? $this->kelas?->nama_kelas,
            ],
        ];
    }
}

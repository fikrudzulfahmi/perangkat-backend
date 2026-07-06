<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlottingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'tahun_pelajaran_id' => $this->tahun_pelajaran_id,
            'tahun_pelajaran'    => $this->tahunPelajaran->nama_tahun ?? '-',
            'guru_id'            => $this->guru_id,
            'guru'               => $this->guru->name ?? '-',
            'mapel_id'           => $this->mapel_id,
            'mapel'              => $this->mapel->nama_mapel ?? '-',
            'jp_per_minggu'      => $this->jp_per_minggu,

            // Output berbentuk array daftar kelas untuk di-looping di Vue.js
            'list_kelas'         => $this->listKelas->map(function ($kelas) {
                return [
                    'id'         => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas ?? '-',
                ];
            }),
        ];
    }
}

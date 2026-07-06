<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nisn' => $this->nisn,
            'nis' => $this->nis,
            'nama_siswa' => $this->nama_siswa,
            'kelas_id' => $this->kelas_id,
            'tahun_pelajaran_id' => $this->tahun_pelajaran_id,
            // Jika relasi kelas diload, tampilkan data kelasnya
            'kelas' => $this->whenLoaded('kelas'),
        ];
    }
}

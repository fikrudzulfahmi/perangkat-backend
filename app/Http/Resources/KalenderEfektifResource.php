<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class KalenderEfektifResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tahun_pelajaran_id' => $this->tahun_pelajaran_id,
            'semester' => $this->semester, // <--- INI TAMBAHANNYA
            'bulan' => $this->bulan,
            'jumlah_minggu' => $this->jumlah_minggu,
            'minggu_efektif' => $this->minggu_efektif,
            'minggu_tidak_efektif' => $this->minggu_tidak_efektif,
            'keterangan' => $this->keterangan ?? '-',
            'file_pdf_url' => $this->file_pdf ? Storage::url($this->file_pdf) : null,
            'tahun_pelajaran' => $this->whenLoaded('tahunPelajaran'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

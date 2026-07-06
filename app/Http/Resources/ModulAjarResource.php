<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModulAjarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plotting_id' => $this->plotting_id,
            'bab_atau_materi' => $this->bab_atau_materi,
            'pertemuan_ke' => $this->pertemuan_ke,
            'alokasi_waktu' => $this->alokasi_waktu,
            'profil_pancasila' => $this->profil_pancasila,
            'sarana_prasarana' => $this->sarana_prasarana,
            'target_peserta' => $this->target_peserta,
            'model_pembelajaran' => $this->model_pembelajaran,
            'pertanyaan_pemantik' => $this->pertanyaan_pemantik,
            'pemahaman_bermakna' => $this->pemahaman_bermakna,
            'kegiatan_pembelajaran' => $this->kegiatan_pembelajaran,
            'lkpd' => $this->lkpd,
            'glosarium_pustaka' => $this->glosarium_pustaka,
            'created_at' => $this->created_at,

            // Relasi (Load jika dipanggil)
            'tujuan_pembelajarans' => $this->whenLoaded('tujuanPembelajarans'),
            'bank_soals' => $this->whenLoaded('bankSoals'),
            'plotting' => $this->whenLoaded('plotting'),
        ];
    }
}

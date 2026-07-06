<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankSoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'plotting_id'       => $this->plotting_id,
            'tp_id'             => $this->tp_id,
            'deskripsi_tp'      => $this->whenLoaded('tujuanPembelajaran', function () {
                return $this->tujuanPembelajaran->deskripsi ?? 'TP Tidak Diketahui'; // Sesuaikan field deskripsi di tabel TP-mu
            }),
            'jenis_asesmen'     => $this->jenis_asesmen,
            'tipe_soal'         => $this->tipe_soal,
            'tingkat_kesulitan' => $this->tingkat_kesulitan,
            'bobot_nilai'       => $this->bobot_nilai,
            'pertanyaan'        => $this->pertanyaan,
            'pilihan_jawaban'   => $this->pilihan_jawaban, // Sudah otomatis array karena casts
            'kunci_jawaban'     => $this->kunci_jawaban,
            'created_at'        => $this->created_at,
        ];
    }
}

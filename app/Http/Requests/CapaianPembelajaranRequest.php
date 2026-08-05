<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapaianPembelajaranRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'mapel_id'  => 'required|exists:mata_pelajarans,id',
            'fase'      => 'required|string|max:2',
            'elemen'    => 'required|string|max:255',
            'deskripsi' => 'required|string',
            // Hasil Analisis CP (KSP) - opsional
            'kompetensi'          => 'nullable|string',
            'konten_materi'       => 'nullable|string',
            'bentuk_pemahaman'    => 'nullable|string',
        ];
    }
}

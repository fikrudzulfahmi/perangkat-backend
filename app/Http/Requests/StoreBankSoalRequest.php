<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plotting_id'       => 'required|uuid|exists:plottings,id',
            'tp_id'             => 'nullable|uuid|exists:tujuan_pembelajarans,id',
            'jenis_asesmen'     => 'required|in:Formatif,Sumatif',
            'tipe_soal'         => 'required|in:Pilihan Ganda,Esai,Praktik/Unjuk Kerja',
            'tingkat_kesulitan' => 'required|in:Mudah,Sedang,Sulit',
            'bobot_nilai'       => 'required|integer|min:1|max:100',
            'pertanyaan'        => 'required|string',
            'pilihan_jawaban'   => 'nullable|array', // Harus array (dari Vue dikirim sebagai array)
            'kunci_jawaban'     => 'nullable|string',
        ];
    }
}

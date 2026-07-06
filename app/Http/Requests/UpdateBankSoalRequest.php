<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // sometimes|required = JIKA field ini dikirim dari Vue, maka WAJIB ADA isinya (tidak boleh kosong)
            'plotting_id'       => 'sometimes|required|uuid|exists:plottings,id',

            // sometimes|nullable = JIKA dikirim dari Vue, boleh kosong (null), tapi kalau diisi harus format UUID
            'tp_id'             => 'sometimes|nullable|uuid|exists:tujuan_pembelajarans,id',

            'jenis_asesmen'     => 'sometimes|required|in:Formatif,Sumatif',
            'tipe_soal'         => 'sometimes|required|in:Pilihan Ganda,Esai,Praktik/Unjuk Kerja',
            'tingkat_kesulitan' => 'sometimes|required|in:Mudah,Sedang,Sulit',

            // tambahkan sometimes|required di sini
            'bobot_nilai'       => 'sometimes|required|integer|min:1|max:100',
            'pertanyaan'        => 'sometimes|required|string',

            // Pilihan jawaban dan kunci bisa kosong (nullable) tergantung tipe soal
            'pilihan_jawaban'   => 'sometimes|nullable|array',
            'kunci_jawaban'     => 'sometimes|nullable|string',
        ];
    }
}

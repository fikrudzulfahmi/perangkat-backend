<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DokumenStatisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => 'required|string|in:kode_etik,ikrar_guru,tata_tertib,pembiasaan_guru',
            'isi'   => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.in' => 'Jenis dokumen tidak valid.',
            'isi.required' => 'Isi dokumen tidak boleh kosong.',
        ];
    }
}

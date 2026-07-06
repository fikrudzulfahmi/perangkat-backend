<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiswaActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware role:admin di route sudah mengatasi hak akses
    }

    public function rules(): array
    {
        return [
            'kelas_id' => 'required|uuid|exists:kelas,id',
            'tahun_pelajaran_id' => 'required|uuid|exists:tahun_pelajarans,id',
        ];
    }
}

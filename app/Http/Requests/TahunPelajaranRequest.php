<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TahunPelajaranRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nama_tahun' => 'required|string|max:20',
            'is_active'  => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'nama_tahun.required' => 'Tahun pelajaran (misal: 2026/2027) wajib diisi.',
            'is_active.required'  => 'Status aktif wajib diisi.',
        ];
    }
}

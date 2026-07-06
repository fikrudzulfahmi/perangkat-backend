<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetKktpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mapel_id' => 'required|uuid|exists:mata_pelajarans,id', // Sesuaikan nama tabel
            'kelas_id' => 'required|uuid|exists:plottings,id', // 🟢 Pastikan ID ada di tabel plottings
        ];
    }
}

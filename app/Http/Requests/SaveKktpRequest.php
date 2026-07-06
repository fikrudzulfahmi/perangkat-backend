<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveKktpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kelas_id' => 'required|uuid|exists:plottings,id', // 🟢 Pastikan ID ada di tabel plottings
            'items' => 'required|array',
            'items.*.tujuan_pembelajaran_id' => 'required|uuid|exists:tujuan_pembelajarans,id',
            'items.*.target_nilai' => 'required|integer|min:1|max:100',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveProsemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plotting_id' => 'required|uuid',
            'items' => 'required|array',
            'items.*.tujuan_pembelajaran_id' => 'required|uuid',
            'items.*.bulan' => 'required|integer|min:1|max:12',
            'items.*.minggu_ke' => 'required|integer|min:1|max:5',
            'items.*.alokasi_jp' => 'required|integer|min:0',
        ];
    }
}

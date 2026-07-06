<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TujuanPembelajaranRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'capaian_pembelajaran_id' => 'required|exists:capaian_pembelajarans,id',
            'kode_tp'                 => 'required|string|max:20',
            'deskripsi'               => 'required|string',
        ];
    }
}

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
        ];
    }
}

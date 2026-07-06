<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KelasRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('kela'); // Mengambil parameter ID dari route 'kelas' (Laravel menyingkatnya secara jamak menjadi kela)

        return [
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas,' . $id,
        ];
    }

    public function messages()
    {
        return [
            'nama_kelas.required' => 'Nama kelas tidak boleh kosong.',
            'nama_kelas.unique'   => 'Nama kelas ini sudah terdaftar.',
        ];
    }
}

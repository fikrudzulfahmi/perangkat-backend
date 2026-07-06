<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MataPelajaranRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Wajib diubah ke true agar diizinkan lolos
    }

    public function rules()
    {
        // Menangkap ID UUID dari URL route untuk pengecualian unique saat edit data
        $id = $this->route('mapel');

        return [
            'kode_mapel' => 'required|string|max:50|unique:mata_pelajarans,kode_mapel,' . $id,
            'nama_mapel' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'kode_mapel.required' => 'Kode mata pelajaran tidak boleh kosong.',
            'kode_mapel.unique'   => 'Kode mata pelajaran ini sudah terdaftar.',
            'nama_mapel.required' => 'Nama mata pelajaran tidak boleh kosong.',
        ];
    }
}

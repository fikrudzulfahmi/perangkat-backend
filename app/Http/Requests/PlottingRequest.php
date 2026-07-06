<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlottingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'tahun_pelajaran_id' => 'required|uuid',
            'guru_id'            => 'required|uuid',
            'mapel_id'           => 'required|uuid',

            // 1. Ubah kelas_id menjadi kelas_ids dan pastikan formatnya adalah array
            'kelas_ids'          => 'required|array|min:1',

            // 2. Validasi setiap isi di dalam array harus berupa UUID dan ada di tabel kelas
            // (Sesuaikan 'kelas,id' dengan nama tabel kelas Anda di database, misal: 'm_kelas,id')
            'kelas_ids.*'        => 'required|uuid|exists:kelas,id',

            'jp_per_minggu'      => 'required|integer|min:1',
        ];
    }
}

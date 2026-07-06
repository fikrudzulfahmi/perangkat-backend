<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JadwalMengajarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tahun_pelajaran_id' => 'required|exists:tahun_pelajarans,id',
            'guru_id'            => 'required|exists:users,id',
            'mata_pelajaran_id' => 'required|exists:mata_pelajarans,id', // Sesuaikan nama tabel mapel
            'kelas_id'           => 'required|exists:kelas,id',
            'blok'               => 'required|string',
            'hari'               => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke'             => 'required|string',
        ];
    }
}

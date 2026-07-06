<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mapel_id' => 'required|exists:mata_pelajarans,id', // Sesuaikan dengan nama tabel mapel Anda

            // 🛠️ PERUBAHAN DI SINI:
            // Diarahkan ke tabel ploting induk karena frontend mengirim plot.id dengan key 'kelas_id'
            // Catatan: Sesuaikan 'plotings' dengan nama tabel ploting Anda di database (apakah 'ploting' atau 'plotings')
            'kelas_id' => 'required|exists:plottings,id',

            'items'    => 'required|array',
            'items.*.tujuan_pembelajaran_id' => 'required|exists:tujuan_pembelajarans,id',
            'items.*.semester'               => 'required|in:1,2',
            'items.*.nomor_urut'             => 'required|integer|min:1',
            'items.*.alokasi_jp'             => 'required|integer|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBukuPeganganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asumsi middleware role sudah menangani otorisasi
    }

    public function rules(): array
    {
        return [
            'plotting_id' => 'required|uuid|exists:plottings,id',
            'judul_buku'  => 'required|string|max:255',
            'penulis'     => 'nullable|string|max:255',
            'penerbit'    => 'nullable|string|max:255',
            'tahun_terbit' => 'nullable|digits:4|integer|min:1900|max:' . (date('Y') + 1),
            'jenis_buku'  => 'required|in:Buku Guru,Buku Siswa,Referensi Lain',
        ];
    }
}

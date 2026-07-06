<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModulAjarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Pastikan middleware role sudah menangani otorisasi
    }

    public function rules(): array
    {
        return [
            'plotting_id' => 'required|uuid|exists:plottings,id',
            'bab_atau_materi' => 'required|string|max:255',
            'pertemuan_ke' => 'required|string|max:50',
            'alokasi_waktu' => 'required|string|max:100',

            // Validasi JSON / Array
            'profil_pancasila' => 'required|array',
            'profil_pancasila.*' => 'string', // Isi array-nya harus string

            'sarana_prasarana' => 'nullable|string',
            'target_peserta' => 'required|string|max:100',
            'model_pembelajaran' => 'required|string|max:150',
            'pertanyaan_pemantik' => 'nullable|string',
            'pemahaman_bermakna' => 'nullable|string',

            // Validasi format struktur Kegiatan Pembelajaran
            'kegiatan_pembelajaran' => 'required|array',
            'kegiatan_pembelajaran.*.tahap' => 'required|string',
            'kegiatan_pembelajaran.*.durasi' => 'required|string',
            'kegiatan_pembelajaran.*.aktivitas' => 'required|string',

            'lkpd' => 'nullable|string',
            'glosarium_pustaka' => 'nullable|string',

            // Validasi Relasi Many-to-Many (Pivot)
            'tujuan_pembelajaran_ids' => 'nullable|array',
            'tujuan_pembelajaran_ids.*' => 'uuid|exists:tujuan_pembelajarans,id',

            'bank_soal_ids' => 'nullable|array',
            'bank_soal_ids.*' => 'uuid|exists:bank_soals,id',
        ];
    }
}

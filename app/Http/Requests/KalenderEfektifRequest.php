<?php

namespace App\Http\Requests; // 🟢 Namespace diubah langsung ke folder utama Requests

use Illuminate\Foundation\Http\FormRequest;

class KalenderEfektifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tahun_pelajaran_id' => 'required|uuid|exists:tahun_pelajarans,id',
            'file_pdf' => 'nullable|file|mimes:pdf|max:5000',
            'rincian' => 'required|array|min:1',
            'rincian.*.bulan' => 'required|string|max:20',
            'rincian.*.jumlah_minggu' => 'required|integer|min:0',
            'rincian.*.minggu_efektif' => 'required|integer|min:0',
            'rincian.*.minggu_tidak_efektif' => 'required|integer|min:0',
            'rincian.*.keterangan' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'tahun_pelajaran_id.required' => 'Tahun pelajaran wajib dipilih.',
            'file_pdf.mimes' => 'File kalender akademik harus berformat PDF.',
            'file_pdf.max' => 'Ukuran file PDF maksimal adalah 5MB.',
            'rincian.required' => 'Data rincian bulan tidak boleh kosong.',
            'rincian.*.jumlah_minggu.required' => 'Jumlah minggu wajib diisi.',
            'rincian.*.minggu_efektif.required' => 'Minggu efektif wajib diisi.',
            'rincian.*.minggu_tidak_efektif.required' => 'Minggu tidak efektif wajib diisi.',
        ];
    }
}

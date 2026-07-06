<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\Plotting;
use App\Models\CapaianPembelajaran;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class BankSoalTemplateExport implements WithHeadings, WithTitle, WithEvents
{
    protected $plottingId;

    public function __construct($plottingId)
    {
        $this->plottingId = $plottingId;
    }

    public function title(): string
    {
        return 'Template Bank Soal';
    }

    // Susunan kolom template Excel kita
    public function headings(): array
    {
        return [
            'Jenis Asesmen',
            'Tipe Soal',
            'Tingkat Kesulitan',
            'Bobot Nilai',
            'Kode TP (Pilih Dropdown)',
            'Pertanyaan / Instruksi',
            'Opsi A',
            'Opsi B',
            'Opsi C',
            'Opsi D',
            'Opsi E',
            'Kunci Jawaban'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Ambil list TP dari database berdasarkan Mapel aktif
                $plotting = Plotting::find($this->plottingId);
                $listKodeTp = [];

                if ($plotting) {
                    $cps = CapaianPembelajaran::with('listTp')
                        ->where('mapel_id', $plotting->mapel_id)
                        ->get();

                    foreach ($cps as $cp) {
                        // Pastikan relasinya sesuai, misal list_tp atau listTp
                        $targetTp = $cp->list_tp ?? $cp->listTp ?? [];
                        foreach ($targetTp as $tp) {
                            $listKodeTp[] = $tp->kode_tp; // Kumpulkan kode seperti '5.1', '5.2'
                        }
                    }
                }

                // 2. Definisikan isi dropdown untuk masing-masing opsi
                $jenisAsesmen     = '"Formatif,Sumatif"';
                $tipeSoal         = '"Pilihan Ganda,Esai,Praktik/Unjuk Kerja"';
                $tingkatKesulitan = '"Mudah,Sedang,Sulit"';
                $kunciJawab       = '"A,B,C,D,E"';

                // Gabungkan kode TP menjadi teks pisahan koma untuk Excel formula
                $tpFormula = count($listKodeTp) > 0 ? '"' . implode(',', $listKodeTp) . '"' : '""';

                // 3. Terapkan dropdown ke baris 2 sampai 100 di Excel agar guru tinggal klik
                for ($i = 2; $i <= 100; $i++) {
                    $this->createDropdown($sheet, 'A' . $i, $jenisAsesmen);     // Kolom A
                    $this->createDropdown($sheet, 'B' . $i, $tipeSoal);         // Kolom B
                    $this->createDropdown($sheet, 'C' . $i, $tingkatKesulitan); // Kolom C

                    if (count($listKodeTp) > 0) {
                        $this->createDropdown($sheet, 'E' . $i, $tpFormula);     // Kolom E (TP)
                    }

                    $this->createDropdown($sheet, 'L' . $i, $kunciJawab);       // Kolom L
                }
            },
        ];
    }

    // Helper fungsi untuk membuat elemen dropdown html/excel
    private function createDropdown($sheet, $cell, $formula)
    {
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);
    }
}

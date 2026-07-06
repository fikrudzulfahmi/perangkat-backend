<?php

namespace App\Services;

use App\Models\BukuPegangan;
use Illuminate\Auth\Access\AuthorizationException;

class BukuPeganganService
{
    public function getBooksByGuru($guruId)
    {
        // Mengambil buku pegangan milik guru login, beserta data plotting dan mapel
        return BukuPegangan::with(['plotting.mapel'])
            ->whereHas('plotting', function ($query) use ($guruId) {
                $query->where('guru_id', $guruId);
                // Disarankan: Tambahkan filter active tahun_pelajaran_id jika ada
            })
            ->latest()
            ->get();
    }

    public function createBook(array $data)
    {
        return BukuPegangan::create($data);
    }

    public function updateBook($id, $guruId, array $data)
    {
        $book = BukuPegangan::findOrFail($id);

        // Keamanan: Pastikan buku ini terikat dengan plotting milik guru yang login
        if ($book->plotting->guru_id !== $guruId) {
            throw new AuthorizationException('Anda tidak memiliki akses untuk mengubah data ini.');
        }

        $book->update($data);
        return $book->load('plotting.mapel');
    }

    public function deleteBook($id, $guruId)
    {
        $book = BukuPegangan::findOrFail($id);

        // Keamanan: Pastikan buku ini terikat dengan plotting milik guru yang login
        if ($book->plotting->guru_id !== $guruId) {
            throw new AuthorizationException('Anda tidak memiliki akses untuk menghapus data ini.');
        }

        return $book->delete();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BukuPeganganService;
use App\Http\Requests\StoreBukuPeganganRequest;
use App\Http\Requests\UpdateBukuPeganganRequest;
use App\Http\Resources\BukuPeganganResource;
use Illuminate\Http\Request;

class BukuPeganganController extends Controller
{
    protected $bukuService;

    public function __construct(BukuPeganganService $bukuService)
    {
        $this->bukuService = $bukuService;
    }

    public function index(Request $request)
    {
        $books = $this->bukuService->getBooksByGuru($request->user()->id);
        return BukuPeganganResource::collection($books);
    }

    public function store(StoreBukuPeganganRequest $request)
    {
        $book = $this->bukuService->createBook($request->validated());

        return response()->json([
            'message' => 'Buku pegangan berhasil disimpan',
            'data'    => new BukuPeganganResource($book)
        ], 201);
    }
    public function update(UpdateBukuPeganganRequest $request, $id)
    {
        try {
            $book = $this->bukuService->updateBook(
                $id,
                $request->user()->id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Buku pegangan berhasil diperbarui',
                'data'    => new BukuPeganganResource($book)
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->bukuService->deleteBook($id, $request->user()->id);

            return response()->json([
                'message' => 'Buku pegangan berhasil dihapus'
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
    public function referensiGlobal()
    {
        // Mengambil data unik berdasarkan judul buku agar tidak banyak duplikat
        // Sesuaikan nama Model BukuPegangan Anda jika berbeda
        $referensi = \App\Models\BukuPegangan::select('judul_buku', 'jenis_buku', 'penulis', 'penerbit', 'tahun_terbit')
            ->distinct()
            ->orderBy('judul_buku', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $referensi
        ]);
    }
}

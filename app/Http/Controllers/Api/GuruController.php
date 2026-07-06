<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGuruRequest;
use App\Http\Resources\GuruResource;
use App\Services\GuruService;
use App\Models\User;

class GuruController extends Controller
{
    protected $guruService;

    // Inject Service melalui Constructor
    public function __construct(GuruService $guruService)
    {
        $this->guruService = $guruService;
    }

    public function index(Request $request)
    {
        // 1. Ambil keyword pencarian dari URL (?search=...)
        $search = $request->query('search');

        $query = User::role('guru'); // Sesuaikan dengan filter role atau tabel Anda

        // 2. Jika ada keyword, filter kolom nama atau email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 3. Eksekusi paginasi (misal: 10 data per halaman)
        // Laravel otomatis membaca parameter ?page=... dari frontend
        $dataGuru = $query->orderBy('name', 'asc')->paginate(10);

        // Kirim kembali dalam bentuk API Resource Collection
        return GuruResource::collection($dataGuru);
    }

    public function store(StoreGuruRequest $request)
    {
        // $request->validated() memastikan hanya data lolos sensor validasi yang masuk
        $guru = $this->guruService->storeGuru($request->validated());

        return (new GuruResource($guru))
            ->additional([
                'status' => 'success',
                'message' => 'Data Guru berhasil ditambahkan!'
            ]);
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        // Validasi data yang masuk. Perhatikan pengecualian email unik untuk ID ini.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8', // nullable: boleh kosong jika tidak ingin ganti password
        ]);

        $guru = $this->guruService->updateGuru($id, $validated);

        return (new GuruResource($guru))->additional([
            'status' => 'success',
            'message' => 'Data Guru berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->guruService->deleteGuru($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru berhasil dihapus!'
        ]);
    }
}

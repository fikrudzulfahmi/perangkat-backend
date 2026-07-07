<?php

namespace App\Services;

use App\Models\Plotting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr; // Tambahkan ini jika ingin pakai Arr::except (opsional)

class PlottingService
{
    public function ambilPaginasi($perPage = 30, $tahunPelajaranId = null, $search = null)
    {
        $query = Plotting::with(['tahunPelajaran', 'guru', 'mapel', 'listKelas'])
            ->whereHas('tahunPelajaran', function ($q) {
                // Tambahkan filter ini agar yang ditarik hanya yang aktif
                $q->where('is_active', 1); // Sesuaikan dengan nama kolom database Anda ('status' atau 'is_active')
            });
        // 1. Filter Berdasarkan Tahun Pelajaran
        if ($tahunPelajaranId) {
            $query->where('tahun_pelajaran_id', $tahunPelajaranId);
        }

        // 2. Filter Berdasarkan Search (Nama Guru atau Nama Mapel)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guru', function ($qGuru) use ($search) {
                    $qGuru->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('mapel', function ($qMapel) use ($search) {
                        $qMapel->where('nama_mapel', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function buatBaru(array $data)
    {
        return DB::transaction(function () use ($data) {
            // PERBAIKAN: Gunakan ?? [] agar tidak error jika kelas_ids kosong/tidak dikirim
            $kelasIds = $data['kelas_ids'] ?? [];
            unset($data['kelas_ids']); // Hapus dari array agar tidak ikut tersimpan ke tabel plottings

            // Simpan data utama
            $plotting = Plotting::create($data);

            // Hubungkan (sync) banyak kelas ke tabel pivot perantara
            // sync() akan otomatis mengisi tabel plotting_kelas
            if (!empty($kelasIds)) {
                $plotting->listKelas()->sync($kelasIds);
            }

            return $plotting->load(['tahunPelajaran', 'guru', 'mapel', 'listKelas']);
        });
    }

    public function perbaruiData(Plotting $plotting, array $data)
    {
        return DB::transaction(function () use ($plotting, $data) {
            // PERBAIKAN: Gunakan ?? [] agar aman
            $kelasIds = $data['kelas_ids'] ?? [];
            unset($data['kelas_ids']);

            // Update data utama
            $plotting->update($data);

            // Perbarui hubungan kelas di tabel pivot perantara
            // sync() sangat canggih: akan menambah yang baru, dan menghapus yang tidak diceklis lagi
            $plotting->listKelas()->sync($kelasIds);

            return $plotting->load(['tahunPelajaran', 'guru', 'mapel', 'listKelas']);
        });
    }

    public function hapusData(Plotting $plotting)
    {
        // Catatan: Jika di migration tabel pivot kamu sudah pakai onDelete('cascade'), 
        // data di tabel plotting_kelas akan otomatis terhapus.
        // Tapi untuk lebih aman, kita detach (lepaskan) dulu relasinya sebelum dihapus.
        $plotting->listKelas()->detach();

        return $plotting->delete();
    }

    public function getPlottingByGuru(string $guruId)
    {
        return Plotting::with(['listKelas', 'mapel', 'tahunPelajaran'])
            ->where('guru_id', $guruId)
            // 🟢 PERBAIKAN KUNCI: Mencegah data bocor dari tahun pelajaran yang tidak aktif
            ->whereHas('tahunPelajaran', function ($query) {
                // Sesuaikan nama kolom status Anda, misal 'status' => 'aktif' atau 'is_active' => 1
                $query->where('is_active', 1);
            })
            ->get();
    }
}

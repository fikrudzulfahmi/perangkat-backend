<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuruService
{
    public function getAllGuru()
    {
        return User::where('role', 'guru')->get();
    }

    public function storeGuru(array $data)
    {
        $guru = User::create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'guru', // Tetap biarkan jika kolom ini masih Anda gunakan di database
            'password' => Hash::make($data['password']),
        ]);

        // 2. Suntikkan role 'guru' via Spatie
        $guru->assignRole('guru');

        // 3. Kembalikan data guru yang sudah siap
        return $guru;
    }

    public function updateGuru($id, array $data)
    {
        $guru = User::findOrFail($id);

        $guru->name = $data['name'];
        $guru->email = $data['email'];

        // Jika password diisi, maka update passwordnya. Jika kosong, biarkan password lama.
        if (!empty($data['password'])) {
            $guru->password = Hash::make($data['password']);
        }

        $guru->save();
        return $guru;
    }

    public function deleteGuru($id)
    {
        $guru = User::findOrFail($id);
        return $guru->delete();
    }
}

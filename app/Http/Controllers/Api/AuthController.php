<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input dari Frontend Vue.js
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari user di database berdasarkan email yang diinput
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user ditemukan DAN password-nya cocok (di-hash)
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Jika salah, kirim respon error ke Vue.js dengan kode status 401 (Unauthorized)
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password yang Anda masukkan salah.'
            ], 401);
        }

        // 4. Jika benar, buatkan Token Keamanan baru menggunakan Laravel Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Kirim respon sukses beserta tokennya ke Vue.js dengan kode status 200 (OK)
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil!',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]
        ], 200);
    }
}

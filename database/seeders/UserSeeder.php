<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ubah guard_name menjadi 'sanctum' sesuai kebutuhan Laravel API Anda 🛠️
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $roleGuru = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'sanctum']);

        // 2. Buat / Update akun Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Perangkat',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->syncRoles($roleAdmin);

        // 3. Buat / Update akun Guru percobaan
        $guru = User::updateOrCreate(
            ['email' => 'fikrudzulfahmi@gmail.com'],
            [
                'name' => 'Fikru Dzul Fahmi',
                'password' => Hash::make('password123'),
            ]
        );
        $guru->syncRoles($roleGuru);
    }
}

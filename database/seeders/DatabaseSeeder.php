<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Memanggil UserSeeder agar dieksekusi saat command artisan run
        $this->call([
            UserSeeder::class,
        ]);
    }
}

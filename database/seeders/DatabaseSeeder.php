<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(['username' => 'cashier'], [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'cashier',
        ]);

        User::firstOrCreate(['username' => env('SUPERADMIN_USERNAME', 'niborobin')], [
            'name' => env('SUPERADMIN_NAME', 'POS Superadmin'),
            'email' => env('SUPERADMIN_EMAIL', 'niborobin@example.com'),
            'password' => env('SUPERADMIN_PASSWORD', 'Etivacsilog2023'),
            'role' => 'superadmin',
        ]);
    }
}

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
        // Akun demo, kata sandi: "password".
        User::factory()->create([
            'name' => 'Pengusul Demo',
            'email' => 'pengusul@example.com',
        ]);

        User::factory()->reviewer()->create([
            'name' => 'Reviewer Demo',
            'email' => 'reviewer@example.com',
        ]);
    }
}

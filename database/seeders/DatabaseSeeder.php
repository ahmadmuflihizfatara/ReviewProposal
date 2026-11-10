<?php

namespace Database\Seeders;

use App\Models\Budget;
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

        User::factory()->kaprodi()->create([
            'name' => 'Kaprodi Demo',
            'email' => 'kaprodi@example.com',
        ]);

        User::factory()->sekretaris()->create([
            'name' => 'Sekretaris Demo',
            'email' => 'sekretaris@example.com',
        ]);

        Budget::current()->update(['amount' => 100_000_000]);
    }
}

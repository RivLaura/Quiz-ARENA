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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // User::factory(10)->create();

        $admin = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'password',
            ],
        );

        $admin->forceFill(['role' => 'admin'])->save();
    }
}

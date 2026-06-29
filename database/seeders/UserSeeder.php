<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() < 8) {
            User::factory()->count(8 - User::count())->create();
        }

        User::updateOrCreate(
            ['email' => 'user@drasa.test'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}

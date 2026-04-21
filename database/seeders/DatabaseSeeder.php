<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'username' => 'testadmin',
            'email'    => 'admin@example.com',
        ]);

        User::factory()->create([
            'username' => 'testfunder',
            'email'    => 'funder@example.com',
        ]);

        User::factory()->create([
            'username' => 'testhunter',
            'email'    => 'hunter@example.com',
        ]);
    }
}

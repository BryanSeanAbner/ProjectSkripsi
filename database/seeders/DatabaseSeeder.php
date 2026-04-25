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
        // Password default: '1'
        $password = \Illuminate\Support\Facades\Hash::make('1');

        \Illuminate\Support\Facades\DB::statement("
            INSERT INTO users (id, username, password, created_at, updated_at)
            SELECT DISTINCT user_id, user_id, '{$password}', NOW(), NOW()
            FROM user_density
            ON DUPLICATE KEY UPDATE updated_at = NOW();
        ");
    }
}

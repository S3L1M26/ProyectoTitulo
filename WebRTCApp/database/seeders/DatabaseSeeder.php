<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::connection('mysql')->table('users')->insert([
            [
                'id' => 1,
                'name' => 'User',
                'email' => 'user@gmail.com',
                'password' => '12345678',
                'role' => 'user',
            ],
    
            [
                'id' => 2,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => '123456789',
                'role' => 'admin',
            ],
        ]);
    }
}

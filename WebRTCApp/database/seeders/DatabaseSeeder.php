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
                'password' => '$2y$12$TQfg7J51zW8ut/gQEtDbPue66HY2cFcZl4WCBaT4DmoCYcuC41yWS',
                'role' => 'user',
            ],
    
            [
                'id' => 2,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => '$2y$12$pnRJU2kQAwkfhpqgaKNc1uEPijMfhKbmdMNkfkaFO73lsWiFBik8i',
                'role' => 'admin',
            ],
        ]);
    }
}
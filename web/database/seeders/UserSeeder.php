<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert 3 records into the users table
        DB::table('users')->insert([
            [
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => null,
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => null,
            ],
        ]);
    }
}

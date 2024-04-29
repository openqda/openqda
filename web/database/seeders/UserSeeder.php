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
                'name' => 'Alessandro Belli',
                'email' => 'belli@uni-bremen.de',
                'email_verified_at' => now(),
                'password' => Hash::make('26e3nesy'),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => null,
            ], [
                'name' => 'Jan Küster',
                'email' => 's_ufzdc2@uni-bremen.de',
                'email_verified_at' => now(),
                'password' => Hash::make('1q2w3e4r5t'),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => null,
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'current_team_id' => null,
                'profile_photo_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => null,
            ],
            [
                'name' => 'User 3',
                'email' => 'user3@example.com',
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

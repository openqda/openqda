<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('projects')->insert([
            [
                'name' => 'Project 1',
                'description' => 'Description for Project 1',
                'origin' => 'Origin 1',
                'creating_user_id' => 1,  // Assuming user ID 1 exists
                'modifying_user_id' => 1, // Assuming user ID 1 exists
                'base_path' => '/path/to/project1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Project 2',
                'description' => 'Description for Project 2',
                'origin' => 'Origin 2',
                'creating_user_id' => 1,  // Assuming user ID 1 exists
                'modifying_user_id' => 1, // Assuming user ID 1 exists
                'base_path' => '/path/to/project2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Project 3',
                'description' => 'Description for Project 3',
                'origin' => null,  // origin is nullable
                'creating_user_id' => 1,  // Assuming user ID 1 exists
                'modifying_user_id' => 1, // Assuming user ID 1 exists
                'base_path' => '/path/to/project3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

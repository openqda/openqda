<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the UserSeeder
        $this->call(UserSeeder::class);

        // Call the ProjectSeeder
        $this->call(ProjectSeeder::class);

        // If you have more seeders, you can continue to add them here.
        // $this->call(AnotherSeeder::class);
    }
}

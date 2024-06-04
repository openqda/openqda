<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'description' => $this->faker->paragraph,
            'origin' => $this->faker->url,
            'team_id' => null, // Assuming a project may or may not have a team
            'creating_user_id' => User::factory(),
            'modifying_user_id' => null,
            'base_path' => $this->faker->filePath(),
        ];
    }
}

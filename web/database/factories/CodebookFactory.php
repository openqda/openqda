<?php

namespace Database\Factories;

use App\Models\Codebook;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodebookFactory extends Factory
{
    protected $model = Codebook::class;

    protected function withFaker()
    {
        return \Faker\Factory::create('en');
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'project_id' => \App\Models\Project::factory(),
        ];
    }
}

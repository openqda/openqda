<?php

namespace Database\Factories;

use App\Models\Code;
use App\Models\Codebook;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeFactory extends Factory
{
    protected $model = Code::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'name' => $this->faker->word,
            'color' => $this->faker->hexColor,
            'description' => $this->faker->sentence,
            'parent_id' => null, // Default to no parent
            'codebook_id' => Codebook::factory(),
        ];
    }
}

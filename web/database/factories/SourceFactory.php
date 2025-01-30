<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'creating_user_id' => User::factory(),
            'modifying_user_id' => User::factory(),
            'project_id' => Project::factory(),
            'type' => $this->faker->randomElement(ContentType::getValues()),
            'upload_path' => $this->faker->filePath(),
        ];
    }
}

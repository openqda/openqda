<?php

namespace Database\Factories;

use App\Models\Code;
use App\Models\Project;
use App\Models\Selection;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SelectionFactory extends Factory
{
    protected $model = Selection::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'text' => $this->faker->sentence,
            'description' => $this->faker->sentence,
            'start_position' => '0:100',
            'end_position' => '0:200',
            'project_id' => Project::factory(),
            'creating_user_id' => User::factory(),
            'modifying_user_id' => null,
            'code_id' => Code::factory(),
            'source_id' => Source::factory(),
        ];
    }
}

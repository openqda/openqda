<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'content' => fake()->paragraph(),
            'project_id' => Project::factory(),
            'target' => fake()->uuid(),
            'visibility' => fake()->randomElement([0, 1]),
            'type' => fake()->randomElement([
                Note::SCOPE_SELECTION,
                Note::SCOPE_SOURCE,
                Note::SCOPE_CODE,
                Note::SCOPE_PROJECT,
            ]),
            'scope' => fake()->randomElement([
                '100:1250',
                '',
                '1:1',
            ]),
            'creating_user_id' => User::factory(),
        ];
    }
}

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
            'visibility' => fake()->randomElement([0, 1, 2]),
            'type' => fake()->randomElement(['memo', 'comment', 'annotation']),
            'scope' => fake()->randomElement([
                Note::SCOPE_SELECTION,
                Note::SCOPE_SOURCE,
                Note::SCOPE_CODE,
                Note::SCOPE_PROJECT,
            ]),
            'creating_user_id' => User::factory(),
        ];
    }
}

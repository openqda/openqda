<?php

namespace Database\Factories;

use App\Enums\ModelType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'model_type' => fake()->randomElement(ModelType::cases()),
            'model_id' => fake()->uuid(), // Or could be numeric id
            'values' => [
                'display' => [
                    'theme' => fake()->randomElement(['dark', 'light']),
                    'sidebar' => fake()->randomElement(['expanded', 'collapsed']),
                ],
                'notifications' => [
                    'email' => fake()->boolean(),
                    'push' => fake()->boolean(),
                ],
            ],
        ];
    }

    // State for project settings
    public function forProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => ModelType::Project,
        ]);
    }

    // State for user settings
    public function forUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => ModelType::User,
        ]);
    }
}

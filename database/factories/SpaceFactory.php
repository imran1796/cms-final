<?php

namespace Database\Factories;

use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Space>
 */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $handle = 'space-' . fake()->unique()->numberBetween(1, 99999);

        return [
            'handle' => $handle,
            'name' => fake()->words(2, true),
            'settings' => null,
            'storage_prefix' => 'space_' . fake()->unique()->numberBetween(1, 99999),
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Subtask;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subtask>
 */
class SubtaskFactory extends Factory
{
    protected $model = Subtask::class;

    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'title' => fake()->sentence(3),
            'completed' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed' => true]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['completed' => false]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.6)->paragraph(),
            'completed' => fake()->boolean(20),
            'due_date' => fake()->optional(0.5)->date(),
            'reminder_date' => fake()->optional(0.3)->dateTimeBetween('-1 week', '+1 month'),
            'repeat_type' => fake()->optional(0.3)->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'notes' => fake()->optional(0.4)->paragraph(),
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

    public function overdue(): static
    {
        return $this->state(fn () => [
            'completed' => false,
            'due_date' => now()->subDay()->toDateString(),
        ]);
    }
}

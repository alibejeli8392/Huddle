<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'department' => fake()->randomElement(['Design', 'Engineering', 'People', 'Marketing']),
            'title' => fake()->sentence(4),
            'details' => fake()->optional()->sentence(),
            'due_date' => now()->toDateString(),
            'due_time' => fake()->optional()->time('H:i'),
        ];
    }
}

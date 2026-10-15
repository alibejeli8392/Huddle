<?php

namespace Database\Factories;

use App\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Friendship>
 */
class FriendshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'addressee_id' => User::factory(),
            'status' => FriendshipStatus::Pending,
        ];
    }

    /**
     * Indicate that both people accepted the request.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FriendshipStatus::Accepted,
        ]);
    }

    /**
     * Indicate that the addressee declined the request.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FriendshipStatus::Declined,
        ]);
    }
}

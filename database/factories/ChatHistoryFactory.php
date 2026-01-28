<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatHistory>
 */
class ChatHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_key' => fake()->uuid(),
            'user_key' => fake()->uuid(),
            'message' => fake()->sentence(),
            'sent_at' => now(),
        ];
    }
}

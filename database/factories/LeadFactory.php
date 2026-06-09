<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'insurance_type' => fake()->randomElement(['Health', 'Auto', 'Medicare', 'Life']),
            'lead_score' => fake()->numberBetween(40, 100),
            'priority' => fake()->randomElement(['High', 'Medium', 'Low']),
            'status' => fake()->randomElement(['New', 'Contacted', 'Quoted', 'Submitted', 'Closed']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Assign the lead to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}

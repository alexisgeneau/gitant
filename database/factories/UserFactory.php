<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = fake()->unique()->userName();

        return [
            'username'         => $username,
            'email'            => fake()->unique()->safeEmail(),
            'github_id'        => (string) fake()->unique()->numberBetween(10_000, 99_999_999),
            'gitlab_id'        => null,
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/'.fake()->numberBetween(1, 9_999_999).'?v=4',
            'preferred_locale' => 'en',
            'is_admin'         => false,
            'reputation_score' => 100,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function gitlab(): static
    {
        return $this->state(fn (array $attributes) => [
            'github_id' => null,
            'gitlab_id' => (string) fake()->unique()->numberBetween(10_000, 99_999_999),
        ]);
    }
}

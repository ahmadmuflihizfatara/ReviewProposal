<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Proposal yang nomor registrasinya sudah diisi reviewer.
     */
    public function registered(): static
    {
        return $this->state(fn () => [
            'registration_number' => sprintf('PRP-%s-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
        ]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issuer_id' => User::factory(),
            'title' => fake()->sentence(6),
            'summary' => fake()->paragraph(),
            'file_path' => 'proposals/example.pdf',
        ];
    }
}

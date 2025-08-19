<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content>
 */
class ContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sourceTypes = ['website', 'api', 'file', 'database', 'document'];

        return [
            'team_id' => Team::factory(),
            'user_id' => fake()->boolean(70) ? User::factory() : null,
            'source_type' => fake()->randomElement($sourceTypes),
            'source_id' => fake()->uuid(),
            'source_url' => fake()->boolean(80) ? fake()->url() : null,
            'title' => fake()->sentence(6, true),
            'body' => fake()->paragraphs(3, true),
            'metadata' => fake()->boolean(60) ? [
                'tags' => fake()->words(3),
                'priority' => fake()->randomElement(['low', 'medium', 'high']),
                'processed_at' => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }

    /**
     * Create content for a specific team.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team->id,
        ]);
    }

    /**
     * Create content for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'team_id' => $user->team_id,
        ]);
    }

    /**
     * Create content without a user.
     */
    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Create content of a specific source type.
     */
    public function sourceType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => $type,
        ]);
    }

    /**
     * Create content with rich metadata.
     */
    public function withRichMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'tags' => fake()->words(fake()->numberBetween(1, 5)),
                'priority' => fake()->randomElement(['low', 'medium', 'high']),
                'category' => fake()->word(),
                'author' => fake()->name(),
                'language' => fake()->languageCode(),
                'word_count' => fake()->numberBetween(100, 5000),
                'processed_at' => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
                'version' => fake()->semver(),
            ],
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'author_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'target_type' => 'all',
            'target_id' => null,
            'pinned' => false,
            'published_at' => null,
            'expires_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()->subMinute()]);
    }

    public function pinned(): static
    {
        return $this->state(['pinned' => true]);
    }
}

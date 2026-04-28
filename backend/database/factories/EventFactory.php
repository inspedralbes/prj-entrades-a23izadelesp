<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['movie', 'concert']),
            'image' => null,
        ];
    }

    public function movie(): static
    {
        return $this->state(fn () => ['type' => 'movie']);
    }

    public function concert(): static
    {
        return $this->state(fn () => ['type' => 'concert']);
    }
}

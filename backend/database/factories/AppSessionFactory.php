<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppSession;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppSession>
 */
class AppSessionFactory extends Factory
{
    protected $model = AppSession::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'time' => fake()->time('H:i'),
            'price' => fake()->randomFloat(2, 5, 50),
            'venue_config' => [
                'type' => 'grid',
                'rows' => 5,
                'cols' => 8,
                'layout' => array_fill(0, 5, array_fill(0, 8, 1)),
            ],
        ];
    }

    public function movie(): static
    {
        return $this->state(fn () => [
            'price' => fake()->randomFloat(2, 8, 15),
            'venue_config' => [
                'type' => 'grid',
                'rows' => 5,
                'cols' => 8,
                'layout' => array_fill(0, 5, array_fill(0, 8, 1)),
            ],
        ]);
    }

    public function concert(): static
    {
        return $this->state(fn () => [
            'price' => null,
            'venue_config' => [
                'type' => 'zones',
                'zones' => [
                    ['id' => 'pista', 'name' => 'Pista', 'capacity' => 500, 'price' => 45.00, 'color' => '#10B981'],
                    ['id' => 'vip', 'name' => 'VIP', 'capacity' => 50, 'price' => 120.00, 'color' => '#EF4444'],
                ],
            ],
        ]);
    }
}

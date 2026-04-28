<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppSession;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_email' => null,
            'session_id' => AppSession::factory(),
            'status' => 'confirmed',
            'total' => fake()->randomFloat(2, 10, 200),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_email' => fake()->safeEmail(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}

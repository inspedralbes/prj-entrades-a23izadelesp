<?php

namespace Database\Factories;

use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'session_id' => \App\Models\AppSession::factory(),
            'row' => Str::random(1),
            'number' => $this->faker->numberBetween(1, 20),
            'price' => $this->faker->randomFloat(2, 10, 50),
            'status' => 'available',
        ];
    }
}
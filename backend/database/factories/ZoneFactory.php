<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'session_id' => \App\Models\AppSession::factory(),
            'name' => $this->faker->word(),
            'price' => $this->faker->randomFloat(2, 20, 100),
            'capacity' => $this->faker->numberBetween(50, 500),
            'available' => $this->faker->numberBetween(10, 500),
            'color' => $this->faker->hexColor(),
        ];
    }
}
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OpdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Dinas ' . $this->faker->company,
            'code' => $this->faker->unique()->bothify('OPD-###'),
        ];
    }
}

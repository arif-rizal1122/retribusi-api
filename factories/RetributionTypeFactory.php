<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RetributionTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opd_id' => \App\Models\Opd::factory(),
            'name' => 'Retribusi ' . $this->faker->word,
            'category' => 'pajak',
            'base_amount' => 50000,
            'is_active' => true,
        ];
    }
}

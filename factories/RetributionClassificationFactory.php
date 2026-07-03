<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RetributionClassificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opd_id' => \App\Models\Opd::factory(),
            'retribution_type_id' => \App\Models\RetributionType::factory(),
            'name' => 'Klasifikasi ' . $this->faker->word,
            'code' => $this->faker->unique()->bothify('RC-###'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\RetributionClassification;
use App\Models\RetributionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxpayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opd_id' => \App\Models\Opd::factory(),
            'name' => $this->faker->name,
            'nik' => $this->faker->unique()->numerify('################'),
            'npwpd' => $this->faker->unique()->numerify('P.###########'),
            'address' => $this->faker->address,
        ];
    }
}

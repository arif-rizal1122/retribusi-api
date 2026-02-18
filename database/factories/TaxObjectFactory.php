<?php

namespace Database\Factories;

use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxObjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opd_id' => \App\Models\Opd::factory(),
            'taxpayer_id' => Taxpayer::factory(),
            'retribution_type_id' => RetributionType::factory(),
            'retribution_classification_id' => RetributionClassification::factory(),
            'name' => 'Objek ' . $this->faker->company,
            'nop' => $this->faker->unique()->numerify('74.72.###.###.###.###.#'),
            'latitude' => -5.46 + (rand(-100, 100) / 10000),
            'longitude' => 122.60 + (rand(-100, 100) / 10000),
        ];
    }
}

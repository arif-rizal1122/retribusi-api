<?php

namespace Database\Factories;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetributionRateFactory extends Factory
{
    protected $model = RetributionRate::class;

    public function definition(): array
    {
        return [
            'opd_id' => Opd::factory(),
            'retribution_type_id' => RetributionType::factory(),
            'retribution_classification_id' => RetributionClassification::factory(),
            'name' => 'Tarif ' . $this->faker->words(2, true),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'unit' => 'Bulan',
            'is_active' => true,
        ];
    }
}

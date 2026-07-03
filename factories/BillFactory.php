<?php

namespace Database\Factories;

use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'taxpayer_id' => Taxpayer::factory(),
            'tax_object_id' => TaxObject::factory(),
            'retribution_type_id' => RetributionType::factory(),
            'retribution_classification_id' => RetributionClassification::factory(),
            'bill_number' => $this->faker->unique()->bothify('BILL-2026-#####'),
            'amount' => 100000,
            'status' => 'pending',
            'due_date' => now()->addMonth(),
        ];
    }
}

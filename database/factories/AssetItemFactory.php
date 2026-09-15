<?php

namespace Database\Factories;

use App\Models\AssetItem;
use App\Models\Opd;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetItemFactory extends Factory
{
    protected $model = AssetItem::class;

    public function definition(): array
    {
        return [
            'opd_id' => Opd::factory(),
            'code' => $this->faker->unique()->bothify('ALAT-###'),
            'name' => $this->faker->randomElement([
                'Excavator Komatsu PC200',
                'Bulldozer D65E',
                'Wheel Loader WA500',
                'Vibro Roller 8 Ton',
                'Dump Truck 10 Ton',
                'Sedot Kakus / Mobil Tinja',
            ]),
            'category' => $this->faker->randomElement(['alat-berat', 'kendaraan', 'sedot-kakus']),
            'merk_type' => $this->faker->bothify('Merk ##'),
            'spesifikasi' => $this->faker->sentence,
            'kondisi' => 'Baik',
            'status_operasional' => 'Tersedia',
            'lokasi' => $this->faker->city,
            'tarif' => $this->faker->randomElement([150000, 250000, 350000, 450000]),
            'satuan_tarif' => $this->faker->randomElement(['per jam', 'per hari', 'per rit']),
            'wajib_tronton' => $this->faker->boolean,
            'is_active' => true,
            'is_demo' => false,
        ];
    }

    public function alatBerat(): static
    {
        return $this->state(fn () => [
            'category' => 'alat-berat',
            'tarif' => 350000,
            'satuan_tarif' => 'per jam',
        ]);
    }

    public function tersedia(): static
    {
        return $this->state(fn () => ['status_operasional' => 'Tersedia']);
    }
}
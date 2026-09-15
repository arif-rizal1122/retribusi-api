<?php

namespace Database\Factories;

use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\Taxpayer;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetRentalFactory extends Factory
{
    protected $model = AssetRental::class;

    public function definition(): array
    {
        return [
            'rental_code' => $this->faker->unique()->bothify('SEWA-########'),
            'nomor_kontrak' => $this->faker->numerify('KONTRAK-####'),
            'taxpayer_id' => Taxpayer::factory(),
            'user_id' => null,
            'asset_item_id' => AssetItem::factory(),
            'opd_id' => Opd::factory(),
            'tanggal_mulai' => $this->faker->date(),
            'tanggal_selesai' => $this->faker->date(),
            'lama_sewa' => '8',
            'satuan_sewa' => 'per jam',
            'tarif_per_satuan' => 350000,
            'include_tronton' => false,
            'total_biaya' => 2800000,
            'dp' => 0,
            'sisa_pembayaran' => 2800000,
            'status' => 'pending_verification',
            'lokasi_penggunaan' => $this->faker->streetAddress,
            'jenis_pekerjaan' => $this->faker->sentence(3),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
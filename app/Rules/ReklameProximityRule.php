<?php

namespace App\Rules;

use App\Models\TaxObject;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReklameProximityRule implements ValidationRule
{
    public function __construct(
        private ?float $latitude,
        private ?float $longitude,
        private ?int $excludeId = null,
        private float $minDistanceMeters = 3.0
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->latitude === null || $this->longitude === null) {
            return;
        }

        $conflict = $this->findConflict();
        if ($conflict) {
            $roundedDist = round($conflict['distance'], 2);
            $fail("Titik koordinat reklame terlalu dekat dengan objek reklame yang sudah ada ({$conflict['name']}, jarak: {$roundedDist}m, batas minimum: {$this->minDistanceMeters}m). Mohon verifikasi titik lokasi.");
        }
    }

    /**
     * Check if there is an existing active reklame within minimum distance.
     *
     * @return array{id: int, name: string, distance: float}|null
     */
    public function findConflict(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        // Bounding box pre-filter (~0.001 deg is ~111m)
        $latDelta = 0.001;
        $lonDelta = 0.001;

        $query = TaxObject::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$this->latitude - $latDelta, $this->latitude + $latDelta])
            ->whereBetween('longitude', [$this->longitude - $lonDelta, $this->longitude + $lonDelta])
            ->whereHas('retributionType', function ($q) {
                $q->where('name', 'like', '%reklame%')
                  ->orWhere('category', 'like', '%reklame%');
            });

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        $candidates = $query->get(['id', 'name', 'latitude', 'longitude']);

        foreach ($candidates as $candidate) {
            $dist = $this->haversineDistance(
                (float) $this->latitude,
                (float) $this->longitude,
                (float) $candidate->latitude,
                (float) $candidate->longitude
            );

            if ($dist < $this->minDistanceMeters) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'distance' => $dist,
                ];
            }
        }

        return null;
    }

    /**
     * Calculate Haversine distance between two coordinates in meters.
     */
    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }
}

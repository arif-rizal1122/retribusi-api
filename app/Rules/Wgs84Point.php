<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Citizen MapPicker contract: decimal latitude,longitude (not a polygon). */
class Wgs84Point implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? array_map('trim', explode(',', $value)) : [];
        $decimal = '/\A[+-]?(?:\d+(?:\.\d*)?|\.\d+)\z/';
        if (count($parts) !== 2 || ! preg_match($decimal, $parts[0]) || ! preg_match($decimal, $parts[1])) {
            $fail('Koordinat harus berupa lintang,bujur dalam derajat desimal WGS84.');

            return;
        }

        $latitude = (float) $parts[0];
        $longitude = (float) $parts[1];
        if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
            $fail('Lintang harus antara -90 dan 90, serta bujur antara -180 dan 180.');
        }
    }
}

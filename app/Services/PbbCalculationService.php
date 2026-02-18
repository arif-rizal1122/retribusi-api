<?php

namespace App\Services;

use App\Models\PbbNjopClassification;

class PbbCalculationService
{
    /**
     * Get NJOP value for a specific class code
     */
    public function getNjopValue(string $type, string $classCode): float
    {
        $classification = PbbNjopClassification::where('type', $type)
            ->where('class_code', $classCode)
            ->first();

        return $classification ? (float) $classification->njop_value : 0.0;
    }

    /**
     * Auto-lookup: find the NJOP class based on a value per m²
     * 
     * Logika: Cari kelas yang range-nya mencakup nilai input.
     * - min_value < value <= max_value  (untuk kelas 001-099)
     * - Kelas terakhir (100/040): value <= max_value
     */
    public function findClassByValue(string $type, float $valuePerM2): ?PbbNjopClassification
    {
        // First try exact range match: min_value < value <= max_value
        $cls = PbbNjopClassification::where('type', $type)
            ->where('min_value', '<', $valuePerM2)
            ->where('max_value', '>=', $valuePerM2)
            ->first();

        if ($cls) return $cls;

        // If value is very high (above highest class), return class 001
        $highest = PbbNjopClassification::where('type', $type)
            ->orderBy('max_value', 'desc')
            ->first();
        if ($highest && $valuePerM2 >= $highest->max_value) {
            return $highest;
        }

        // If value is very low (below lowest class), return the last class
        $lowest = PbbNjopClassification::where('type', $type)
            ->orderBy('min_value', 'asc')
            ->first();
        if ($lowest && $valuePerM2 <= $lowest->min_value) {
            return $lowest;
        }

        return null;
    }

    /**
     * Calculate PBB with manual class codes (existing method)
     */
    public function calculate(float $luasBumi, string $classBumi, float $luasBangunan, string $classBangunan, float $njoptkp = 10000000, float $tariff = 0.001): array
    {
        $njopBumiPerM2 = $this->getNjopValue('bumi', $classBumi);
        $njopBangunanPerM2 = $this->getNjopValue('bangunan', $classBangunan);

        return $this->computePbb($luasBumi, $classBumi, $njopBumiPerM2, $luasBangunan, $classBangunan, $njopBangunanPerM2, $njoptkp, $tariff);
    }

    /**
     * Calculate PBB with auto-lookup from values per m²
     * 
     * User cukup input:
     * - luas_bumi, nilai_bumi_per_m2
     * - luas_bangunan, nilai_bangunan_per_m2
     * 
     * Sistem otomatis menentukan kelas NJOP dari Lampiran.
     */
    public function calculateAuto(
        float $luasBumi, float $nilaiBumiPerM2,
        float $luasBangunan, float $nilaiBangunanPerM2,
        float $njoptkp = 10000000, float $tariff = 0.001
    ): array {
        $kelasBumi = $this->findClassByValue('bumi', $nilaiBumiPerM2);
        $kelasBangunan = $this->findClassByValue('bangunan', $nilaiBangunanPerM2);

        $classBumiCode = $kelasBumi ? $kelasBumi->class_code : '-';
        $classBangunanCode = $kelasBangunan ? $kelasBangunan->class_code : '-';
        $njopBumiPerM2 = $kelasBumi ? (float) $kelasBumi->njop_value : 0;
        $njopBangunanPerM2 = $kelasBangunan ? (float) $kelasBangunan->njop_value : 0;

        $result = $this->computePbb($luasBumi, $classBumiCode, $njopBumiPerM2, $luasBangunan, $classBangunanCode, $njopBangunanPerM2, $njoptkp, $tariff);

        // Add auto-lookup details
        $result['auto_lookup'] = true;
        $result['input_nilai_bumi_per_m2'] = $nilaiBumiPerM2;
        $result['input_nilai_bangunan_per_m2'] = $nilaiBangunanPerM2;
        $result['kelas_bumi_range'] = $kelasBumi ? [
            'min' => (float) $kelasBumi->min_value,
            'max' => (float) $kelasBumi->max_value,
        ] : null;
        $result['kelas_bangunan_range'] = $kelasBangunan ? [
            'min' => (float) $kelasBangunan->min_value,
            'max' => (float) $kelasBangunan->max_value,
        ] : null;

        return $result;
    }

    /**
     * Core PBB computation (shared by both manual and auto)
     */
    private function computePbb(
        float $luasBumi, string $classBumi, float $njopBumiPerM2,
        float $luasBangunan, string $classBangunan, float $njopBangunanPerM2,
        float $njoptkp, float $tariff
    ): array {
        $totalNjopBumi = $luasBumi * $njopBumiPerM2;
        $totalNjopBangunan = $luasBangunan * $njopBangunanPerM2;
        $totalNjop = $totalNjopBumi + $totalNjopBangunan;

        $njopKenaPajak = max(0, $totalNjop - $njoptkp);
        $pbbTerhutang = $njopKenaPajak * $tariff;

        return [
            'luas_bumi' => $luasBumi,
            'kelas_bumi' => $classBumi,
            'njop_bumi_per_m2' => $njopBumiPerM2,
            'total_njop_bumi' => $totalNjopBumi,
            'luas_bangunan' => $luasBangunan,
            'kelas_bangunan' => $classBangunan,
            'njop_bangunan_per_m2' => $njopBangunanPerM2,
            'total_njop_bangunan' => $totalNjopBangunan,
            'total_njop' => $totalNjop,
            'njoptkp' => $njoptkp,
            'njop_kena_pajak' => $njopKenaPajak,
            'tariff' => $tariff,
            'pbb_terhutang' => $pbbTerhutang,
        ];
    }
}

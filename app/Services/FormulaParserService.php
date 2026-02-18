<?php

namespace App\Services;

class FormulaParserService
{
    /**
     * Parse and calculate a formula based on provided variables.
     * Example: formula = 'omzet * tariff', variables = ['omzet' => 1000000, 'tariff' => 0.1]
     * Result: 100000.0
     *
     * @param string $formula
     * @param array $variables
     * @return float
     */
    public function calculate(string $formula, array $variables = []): float
    {
        if (empty($formula)) {
            return 0.0;
        }

        // 1. Replace variables with their numeric values
        // Sort keys by length descending to avoid partial replacement (e.g., 'tariff' before 'tarif')
        uksort($variables, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($variables as $key => $value) {
            $formula = str_ireplace($key, (string)($value ?? 0), $formula);
        }

        // 2. Sanitize: Only allow numbers, math operators, dots, and parentheses
        $sanitizedFormula = preg_replace('/[^-+*.\/()0-9 ]/', '', $formula);

        // 3. Basic validity check (prevent empty or malformed strings)
        if (trim($sanitizedFormula) === '' || preg_match('/[+*.\/]{2,}/', $sanitizedFormula)) {
            return 0.0;
        }

        // 4. Evaluate safely
        try {
            // We use a simple evaluation logic. 
            // In a more complex scenario, we could use a proper expression language library.
            $result = 0.0;
            
            // Check if it's a simple math expression
            if (preg_match('/^[-+*.\/()0-9 ]+$/', $sanitizedFormula)) {
                // Use PHP's internal calc if possible or a simple return
                $result = @eval("return $sanitizedFormula;");
            }

            return (float) ($result ?? 0.0);
        } catch (\Throwable $e) {
            \Log::error("Formula calculation error: " . $e->getMessage() . " | Formula: " . $formula);
            return 0.0;
        }
    }

    /**
     * Calculate penalty based on Perwali No. 58/2024.
     * 
     * @param float $amount
     * @param int $monthsLate
     * @param string $type Rate types:
     *   - 'stpd': 1% (Default late payment)
     *   - 'skpdkb': 1.8% (General audit)
     *   - 'jabatan': 2.2% (No reporting/bookkeeping audit)
     *   - 'angsuran' / 'penundaan' / 'salah_hitung': 0.6%
     * @return float
     */
    public function calculatePenalty(float $amount, int $monthsLate, string $type = 'stpd'): float
    {
        $monthsLate = (int) $monthsLate;
        $monthsLate = min($monthsLate, 24);
        if ($monthsLate <= 0) return 0.0;
        
        $rate = match (strtolower($type)) {
            'skpdkb' => 0.018,
            'jabatan' => 0.022,
            'angsuran', 'penundaan', 'salah_hitung', 'restitusi' => 0.006,
            default => 0.01, // Terlambat Bayar / Setor (STPD) 1%
        };
        
        return floor($amount * $rate * $monthsLate);
    }

    /**
     * Get fixed fine amount for not reporting SPTPD.
     */
    public function getFixedFineForNoReporting(): float
    {
        return 100000.0; // Rp 100.000 for not submitting SPTPD
    }
}

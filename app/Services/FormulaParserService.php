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
        // Sort keys by length descending to avoid partial replacement
        uksort($variables, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($variables as $key => $value) {
            $formula = str_ireplace($key, (string)($value ?? 0), $formula);
        }

        // 2. Default remaining word-based variables to 0 to avoid syntax errors
        // This finds words that are not part of functions (like IF) or scientific notation
        $formula = preg_replace_callback('/(?<![0-9a-zA-Z_])[a-zA-Z_][a-zA-Z0-9_]*(?![0-9a-zA-Z_])/', function($m) {
            $word = strtoupper($m[0]);
            if (in_array($word, ['IF', 'AND', 'OR', 'NOT', 'TRUE', 'FALSE'])) {
                return $m[0];
            }
            return '0';
        }, $formula);

        // 3. Add support for IF(cond, true, false) by converting to ternary
        // Pattern: IF(condition, true_val, false_val) -> (condition ? true_val : false_val)
        // This is a simple regex for basic IF nesting
        $formula = preg_replace_callback('/IF\s*\(([^,]+),([^,]+),([^)]+)\)/i', function($m) {
            return "(" . trim($m[1]) . " ? " . trim($m[2]) . " : " . trim($m[3]) . ")";
        }, $formula);

        // 3. Sanitize: Allow numbers, math operators, dots, parentheses, and logical operators
        // Added: > < = ? : ! & | (for logical comparisons and ternary)
        $sanitizedFormula = preg_replace('/[^-+*.\/()0-9 ><=? :!&|]/', '', $formula);

        // 4. Basic validity check
        if (trim($sanitizedFormula) === '') {
            return 0.0;
        }

        // 5. Evaluate safely
        try {
            $result = 0.0;
            
            // We use PHP's eval for power, but with strict character sanitization above.
            // Note: PHP eval requires a semicolon and return.
            $result = @eval("return $sanitizedFormula;");

            return (float) ($result ?? 0.0);
        } catch (\Throwable $e) {
            \Log::error("Formula calculation error: " . $e->getMessage() . " | Original: " . $formula . " | Sanitized: " . $sanitizedFormula);
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

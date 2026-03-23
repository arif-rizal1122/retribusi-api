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

        // 1. Temporarily extract string literals from the formula itself
        $literals = [];
        $formula = preg_replace_callback('/(["\'])(?:(?=(\\\\?))\2.)*?\1/', function($m) use (&$literals) {
            $placeholder = "___LIT_EXT_" . count($literals) . "___";
            $literals[$placeholder] = $m[0];
            return $placeholder;
        }, $formula);

        // 2. Replace variables
        uksort($variables, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($variables as $key => $value) {
            if (is_numeric($value)) {
                $numericValue = (float)$value;
                $formattedValue = number_format($numericValue, 10, '.', '');
                $formattedValue = rtrim(rtrim($formattedValue, '0'), '.');
                $formula = str_ireplace($key, $formattedValue, $formula);
            } else {
                // For strings, create a new literal placeholder to avoid mangling
                $escapedValue = '"' . addslashes((string)$value) . '"';
                $placeholder = "___LIT_VAR_" . count($literals) . "___";
                $literals[$placeholder] = $escapedValue;
                $formula = str_ireplace($key, $placeholder, $formula);
            }
        }

        // 3. Default remaining word-based variables to 0 to avoid syntax errors
        // Except for logical keywords and our literal placeholders
        $formula = preg_replace_callback('/(?<![0-9a-zA-Z_eE])[a-zA-Z_][a-zA-Z0-9_]*(?![0-9a-zA-Z_eE])/', function($m) use ($literals) {
            $word = strtoupper($m[0]);
            if (in_array($word, ['IF', 'AND', 'OR', 'NOT', 'TRUE', 'FALSE']) || isset($literals[$m[0]])) {
                return $m[0];
            }
            return '0';
        }, $formula);

        // 4. Put back ALL string literals
        foreach ($literals as $placeholder => $original) {
            $formula = str_replace($placeholder, $original, $formula);
        }

        // 5. Add support for IF(cond, true, false) by converting to ternary
        $depth = 0;
        while (str_contains(strtoupper($formula), 'IF') && $depth < 10) {
            $newFormula = preg_replace_callback('/IF\s*\(([^,]+),([^,]+),((?:(?>(?:(?!IF\s*\()[^)])+)|(?R))*)\)/i', function($m) {
                return "(" . trim($m[1]) . " ? " . trim($m[2]) . " : " . trim($m[3]) . ")";
            }, $formula);
            
            if ($newFormula === $formula) break;
            $formula = $newFormula;
            $depth++;
        }

        // 6. Sanitize: Allow numbers, math operators, dots, parentheses, quotes, and logical operators
        $sanitizedFormula = preg_replace('/[^-+*.\/()0-9 ><=? :!&|\'\"eEa-zA-Z_]/', '', $formula);

        // 7. Evaluate safely
        if (trim($sanitizedFormula) === '') return 0.0;
        
        try {
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

    // =========================================================================
    // HARDCODED TAX RULES (Audit-Ready / Perda No. 1/2024 & Perwali 58/2024)
    // These constants are LOCKED and CANNOT be overridden by frontend payloads.
    // =========================================================================

    /**
     * Calculate Reklame NSR with locked multipliers.
     * Formula: (NJOPR + NSPR) * 25% * category_multiplier
     *
     * Locked Multipliers (Perda No. 1/2024):
     *   - Rokok/Alkohol: 110% (1.10)
     *   - Mall/Pusat Perbelanjaan: 50% (0.50)
     *   - Umum: 100% (1.00)
     *
     * @param float $njopr Nilai Jual Objek Pajak Reklame
     * @param float $nspr Nilai Strategis Pemasangan Reklame
     * @param string $category 'rokok_alkohol' | 'mall' | 'umum'
     * @return array{nsr: float, tax: float, multiplier: float, tariff: float}
     */
    public function calculateReklameNSR(float $njopr, float $nspr, string $category = 'umum'): array
    {
        $tariff = 0.25; // 25% tarif tetap

        $multiplier = match (strtolower($category)) {
            'rokok_alkohol', 'rokok', 'alkohol' => 1.10,  // 110%
            'mall', 'pusat_perbelanjaan'        => 0.50,  // 50%
            default                             => 1.00,  // 100%
        };

        $nsr = ($njopr + $nspr) * $multiplier;
        $tax = $nsr * $tariff;

        return [
            'nsr'        => round($nsr, 2),
            'tax'        => round($tax, 2),
            'multiplier' => $multiplier,
            'tariff'     => $tariff,
        ];
    }

    /**
     * Calculate BPHTB with hardcoded NPOPTKP deduction.
     * Formula: (NPOP - NPOPTKP) * 5%
     *
     * Locked Constants (UU HKPD / Perda):
     *   - NPOPTKP Umum: Rp 80.000.000
     *   - NPOPTKP Waris/Hibah Wasiat: Rp 300.000.000
     *
     * @param float $npop Nilai Perolehan Objek Pajak
     * @param string $acquisitionType 'umum' | 'waris' | 'hibah_wasiat'
     * @return array{npoptkp: float, taxable: float, tax: float, tariff: float}
     */
    public function calculateBPHTB(float $npop, string $acquisitionType = 'umum'): array
    {
        $tariff = 0.05; // 5%

        $npoptkp = match (strtolower($acquisitionType)) {
            'waris', 'hibah_wasiat', 'hibah' => 300000000.0, // Rp 300 Juta
            default                          => 80000000.0,  // Rp 80 Juta
        };

        $taxable = max(0, $npop - $npoptkp);
        $tax = $taxable * $tariff;

        return [
            'npoptkp' => $npoptkp,
            'taxable' => round($taxable, 2),
            'tax'     => round($tax, 2),
            'tariff'  => $tariff,
        ];
    }

    /**
     * Validate Air Tanah (PAT) SPOPD fields.
     * Throws ValidationException if meteran_air is absent AND no estimation justification.
     *
     * @param array $metadata The object_data / metadata from SPOPD form
     * @return array Validated & enriched metadata
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateAirTanah(array $metadata): array
    {
        $tujuan = $metadata['tujuan_pemanfaatan'] ?? null;
        $meteran = $metadata['meteran_air'] ?? null;

        if (empty($tujuan)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tujuan_pemanfaatan' => 'Tujuan pemanfaatan air tanah wajib diisi (niaga/non_niaga/industri/pdam).',
            ]);
        }

        // If no meter, require estimation justification
        if (strtolower($meteran ?? '') === 'tidak_ada' || empty($meteran)) {
            if (empty($metadata['justifikasi_estimasi'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'justifikasi_estimasi' => 'Jika meteran air tidak tersedia, justifikasi perhitungan estimasi teknis wajib diisi.',
                ]);
            }
            $metadata['metode_perhitungan'] = 'estimasi_teknis';
        } else {
            $metadata['metode_perhitungan'] = 'meteran';
        }

        return $metadata;
    }

    /**
     * Validate MBLB SPOPD fields.
     * Ensures volume/tonase and harga_patokan are present.
     *
     * @param array $metadata
     * @return array Validated metadata
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateMBLB(array $metadata): array
    {
        if (empty($metadata['volume_tonase']) || !is_numeric($metadata['volume_tonase'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'volume_tonase' => 'Volume/tonase material wajib diisi dengan angka.',
            ]);
        }

        if (empty($metadata['harga_patokan']) || !is_numeric($metadata['harga_patokan'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'harga_patokan' => 'Harga patokan mineral wajib diisi dari Master Data.',
            ]);
        }

        $metadata['dasar_pengenaan'] = (float) $metadata['volume_tonase'] * (float) $metadata['harga_patokan'];

        return $metadata;
    }
}

<?php

function calculate(string $formula, array $variables = []): float
{
    if (empty($formula)) {
        return 0.0;
    }

    // 1. Replace variables with their numeric values
    uksort($variables, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($variables as $key => $value) {
        $numericValue = is_numeric($value) ? (float)$value : 0;
        $formattedValue = number_format($numericValue, 10, '.', '');
        $formattedValue = rtrim(rtrim($formattedValue, '0'), '.');
        $formula = str_ireplace($key, $formattedValue, $formula);
    }

    echo "After variables: $formula\n";

    // 2. Default remaining word-based variables to 0
    $formula = preg_replace_callback('/(?<![0-9a-zA-Z_])[a-zA-Z_][a-zA-Z0-9_]*(?![0-9a-zA-Z_])/', function($m) {
        $word = strtoupper($m[0]);
        if (in_array($word, ['IF', 'AND', 'OR', 'NOT', 'TRUE', 'FALSE'])) {
            return $m[0];
        }
        return '0';
    }, $formula);

    echo "After word defaults: $formula\n";

    // 3. IF support
    $formula = preg_replace_callback('/IF\s*\(([^,]+),([^,]+),([^)]+)\)/i', function($m) {
        return "(" . trim($m[1]) . " ? " . trim($m[2]) . " : " . trim($m[3]) . ")";
    }, $formula);

    // 3. Sanitize
    $sanitizedFormula = preg_replace('/[^-+*.\/()0-9 ><=? :!&|]/', '', $formula);

    echo "Sanitized: $sanitizedFormula\n";

    // 5. Evaluate safely
    try {
        $result = eval("return $sanitizedFormula;");
        return (float) ($result ?? 0.0);
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return 0.0;
    }
}

$formula = '(njop - 10000000) * 0.003';
$variables = ['njop' => '12000000000000'];

echo "Calculating: $formula with njop=12000000000000\n";
$result = calculate($formula, $variables);
echo "Result: $result\n";
echo "Formatted: Rp " . number_format($result, 0, ',', '.') . "\n";

echo "\nTesting scientific notation case:\n";
$variables = ['njop' => 1200000000000000000.0]; // Large float
$result = calculate($formula, $variables);
echo "Result: $result\n";

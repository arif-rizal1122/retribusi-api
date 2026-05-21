<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Services\FormulaParserService;
use App\Services\PbbCalculationService;

class TaxCalculationService
{
    protected $formulaParser;
    protected $pbbCalculationService;

    public function __construct(FormulaParserService $formulaParser, PbbCalculationService $pbbCalculationService)
    {
        $this->formulaParser = $formulaParser;
        $this->pbbCalculationService = $pbbCalculationService;
    }

    public function calculate(TaxObject $taxObject, array $inputData = [], $report = null): float
    {
        if ($report && $report->status === 'approved') {
            return (float) $report->tax_amount;
        }

        $type = $taxObject->retributionType;

        if (!$type) {
            return 0;
        }

        if (str_contains(strtolower($type->name), 'pbb') || str_contains(strtolower($type->category ?? ''), 'pajak bumi')) {
            $metadata = array_merge($taxObject->metadata ?? [], $inputData);

            $luasBumi = (float) ($metadata['luas_bumi'] ?? $metadata['luas_tanah'] ?? 0);
            $kelasBumi = (string) ($metadata['kelas_bumi'] ?? '');
            $luasBangunan = (float) ($metadata['luas_bangunan'] ?? 0);
            $kelasBangunan = (string) ($metadata['kelas_bangunan'] ?? '');

            $njoptkp = (float) ($metadata['njoptkp'] ?? 10000000);
            $tariff = (float) ($metadata['tariff'] ?? 0.001);

            $result = $this->pbbCalculationService->calculate($luasBumi, $kelasBumi, $luasBangunan, $kelasBangunan, $njoptkp, $tariff);
            return (float) $result['pbb_terhutang'];
        }

        $rate = \App\Models\RetributionRate::where('retribution_type_id', $taxObject->retribution_type_id)
            ->where('retribution_classification_id', $taxObject->retribution_classification_id)
            ->where(function ($q) use ($taxObject) {
                if ($taxObject->zone_id) {
                    $q->where('zone_id', $taxObject->zone_id);
                } else {
                    $q->whereNull('zone_id');
                }
            })
            ->where('is_active', true)
            ->first();

        $variables = array_merge(
            $taxObject->metadata ?? [],
            $inputData,
            [
                'amount' => $rate ? $rate->amount : 0,
                'tariff' => $rate ? ($rate->amount / 100) : 0,
            ]
        );

        if ($rate && $rate->calculation_formula) {
            return $this->formulaParser->calculate($rate->calculation_formula, $variables);
        }

        $classification = $taxObject->classification;
        if ($classification && $classification->calculation_formula) {
            return $this->formulaParser->calculate($classification->calculation_formula, $variables);
        }

        if ($rate) {
            return $rate->amount;
        }

        return (float) ($type->base_amount ?? 0);
    }
}

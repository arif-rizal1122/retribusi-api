<?php

namespace App\Services;

use App\Models\RetributionRate;
use App\Models\TaxObject;

class TaxCalculationService
{
    public function __construct(
        private FormulaParserService $formulaParser
    ) {
    }

    public function calculateAmount(TaxObject $taxObject, array $inputData = [], $report = null): float
    {
        return (float) $this->calculateForObject($taxObject, $inputData, $report)['amount'];
    }

    public function calculateForObject(TaxObject $taxObject, array $inputData = [], $report = null): array
    {
        $taxObject->loadMissing(['retributionType', 'classification']);
        $type = $taxObject->retributionType;

        if (!$type) {
            return [
                'amount' => 0.0,
                'source' => 'no_retribution_type',
                'formula' => null,
                'variables' => $inputData,
            ];
        }

        if ($report) {
            $inputData = $this->mergeReportVariables($inputData, $report);
        }

        if ($this->isPbbObject($taxObject)) {
            $classification = $taxObject->classification;
            $metadata = array_merge($taxObject->metadata ?? [], $inputData);

            if (array_key_exists('njop', $metadata) && $classification && filled($classification->calculation_formula)) {
                $rate = $this->findActiveRate($taxObject);
                $variables = $this->buildVariables($taxObject, $rate, $inputData);

                return [
                    'amount' => max(0, (float) $this->formulaParser->calculate($classification->calculation_formula, $variables)),
                    'source' => 'classification_formula',
                    'formula' => $classification->calculation_formula,
                    'rate_id' => $rate?->id,
                    'variables' => $variables,
                ];
            }

            $result = $this->calculatePbb($taxObject, $inputData);

            return [
                'amount' => (float) $result['pbb_terhutang'],
                'source' => 'pbb_service',
                'formula' => 'PBB-P2',
                'variables' => array_merge($taxObject->metadata ?? [], $inputData),
                'details' => $result,
            ];
        }

        $rate = $this->findActiveRate($taxObject);
        $variables = $this->buildVariables($taxObject, $rate, $inputData);
        $classification = $taxObject->classification;

        if ($rate && filled($rate->calculation_formula)) {
            return [
                'amount' => max(0, (float) $this->formulaParser->calculate($rate->calculation_formula, $variables)),
                'source' => 'rate_formula',
                'formula' => $rate->calculation_formula,
                'rate_id' => $rate->id,
                'variables' => $variables,
            ];
        }

        if ($classification && filled($classification->calculation_formula)) {
            return [
                'amount' => max(0, (float) $this->formulaParser->calculate($classification->calculation_formula, $variables)),
                'source' => 'classification_formula',
                'formula' => $classification->calculation_formula,
                'rate_id' => $rate?->id,
                'variables' => $variables,
            ];
        }

        if ($rate) {
            return [
                'amount' => max(0, (float) $rate->amount),
                'source' => 'rate_amount',
                'formula' => null,
                'rate_id' => $rate->id,
                'variables' => $variables,
            ];
        }

        return [
            'amount' => max(0, (float) ($type->base_amount ?? 0)),
            'source' => 'type_base_amount',
            'formula' => null,
            'variables' => $variables,
        ];
    }

    private function findActiveRate(TaxObject $taxObject): ?RetributionRate
    {
        $query = RetributionRate::where('retribution_type_id', $taxObject->retribution_type_id)
            ->where('is_active', true);

        if ($taxObject->retribution_classification_id) {
            $query->where('retribution_classification_id', $taxObject->retribution_classification_id);
        } else {
            $query->whereNull('retribution_classification_id');
        }

        if ($taxObject->zone_id) {
            $query->where(function ($q) use ($taxObject) {
                $q->where('zone_id', $taxObject->zone_id)
                    ->orWhereNull('zone_id');
            })->orderByRaw('CASE WHEN zone_id IS NULL THEN 1 ELSE 0 END');
        } else {
            $query->whereNull('zone_id');
        }

        $rate = $query->first();

        if (!$rate && $taxObject->retribution_classification_id) {
            $fallbackQuery = RetributionRate::where('retribution_type_id', $taxObject->retribution_type_id)
                ->whereNull('retribution_classification_id')
                ->where('is_active', true);

            if ($taxObject->zone_id) {
                $fallbackQuery->where(function ($q) use ($taxObject) {
                    $q->where('zone_id', $taxObject->zone_id)
                        ->orWhereNull('zone_id');
                })->orderByRaw('CASE WHEN zone_id IS NULL THEN 1 ELSE 0 END');
            } else {
                $fallbackQuery->whereNull('zone_id');
            }

            $rate = $fallbackQuery->first();
        }

        return $rate;
    }

    private function buildVariables(TaxObject $taxObject, ?RetributionRate $rate, array $inputData): array
    {
        $type = $taxObject->retributionType;
        $variables = array_merge($taxObject->metadata ?? [], $inputData);
        $baseAmount = (float) ($type->base_amount ?? 0);
        $rateAmount = $rate ? (float) $rate->amount : $baseAmount;

        $variables['amount'] = $variables['amount'] ?? $rateAmount;
        $variables['base_amount'] = $variables['base_amount'] ?? $baseAmount;
        $variables['rate_amount'] = $variables['rate_amount'] ?? $rateAmount;
        $variables['tariff'] = $variables['tariff'] ?? (
            $rate ? $rateAmount / 100 : (float) ($type->tariff_percent ?? 0) / 100
        );

        if (array_key_exists('turnover_amount', $variables) && !array_key_exists('omzet', $variables)) {
            $variables['omzet'] = $variables['turnover_amount'];
        }

        if (array_key_exists('omzet', $variables) && !array_key_exists('turnover_amount', $variables)) {
            $variables['turnover_amount'] = $variables['omzet'];
        }

        return $variables;
    }

    private function mergeReportVariables(array $inputData, $report): array
    {
        $turnover = $report->turnover_amount ?? null;

        return array_merge($inputData, array_filter([
            'monthly_report_id' => $report->id ?? null,
            'turnover_amount' => $turnover,
            'omzet' => $turnover,
            'reported_tax_amount' => $report->tax_amount ?? null,
        ], fn ($value) => $value !== null));
    }

    private function isPbbObject(TaxObject $taxObject): bool
    {
        $type = $taxObject->retributionType;
        $classification = $taxObject->classification;

        $haystack = strtolower(implode(' ', [
            $type->name ?? '',
            $type->category ?? '',
            $classification->name ?? '',
            $classification->code ?? '',
        ]));

        return str_contains($haystack, 'pbb') || str_contains($haystack, 'pajak bumi');
    }

    private function calculatePbb(TaxObject $taxObject, array $inputData): array
    {
        $metadata = array_merge($taxObject->metadata ?? [], $inputData);
        $pbbService = app(PbbCalculationService::class);

        return $pbbService->calculate(
            (float) ($metadata['luas_bumi'] ?? $metadata['luas_tanah'] ?? 0),
            (string) ($metadata['kelas_bumi'] ?? ''),
            (float) ($metadata['luas_bangunan'] ?? 0),
            (string) ($metadata['kelas_bangunan'] ?? ''),
            (float) ($metadata['njoptkp'] ?? 10000000),
            (float) ($metadata['tariff'] ?? 0.001)
        );
    }
}

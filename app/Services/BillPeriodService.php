<?php

namespace App\Services;

use Carbon\Carbon;

class BillPeriodService
{
    public function getCarbonUnit(string $cycle): string
    {
        return match ($cycle) {
            'daily' => 'day',
            'weekly' => 'week',
            'yearly' => 'year',
            default => 'month',
        };
    }

    public function incrementDate(Carbon $date, string $cycle): void
    {
        match ($cycle) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'yearly' => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    public function getPeriodString(Carbon $date, string $cycle): string
    {
        return match ($cycle) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->format('Y') . '-W' . $date->format('W'),
            'yearly' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    public function getPeriodLabel(Carbon|string $date, string $cycle): string
    {
        $periodDate = $date instanceof Carbon ? $date->copy() : $this->parsePeriod($date, $cycle);

        return match ($cycle) {
            'daily' => $periodDate->translatedFormat('d F Y'),
            'weekly' => 'Minggu ke-' . $periodDate->format('W') . ', ' . $periodDate->format('Y'),
            'yearly' => 'Tahun ' . $periodDate->format('Y'),
            default => $periodDate->translatedFormat('F Y'),
        };
    }

    public function getDueDate(Carbon|string $date, string $cycle): Carbon
    {
        $periodDate = $date instanceof Carbon ? $date->copy() : $this->parsePeriod($date, $cycle);

        return match ($cycle) {
            'daily' => $periodDate->copy()->endOfDay(),
            'weekly' => $periodDate->copy()->endOfWeek(),
            'yearly' => $periodDate->copy()->endOfYear(),
            default => $periodDate->copy()->endOfMonth(),
        };
    }

    public function resolve(?string $period, string $cycle, ?Carbon $fallbackDate = null): array
    {
        $periodDate = $this->parsePeriod($period, $cycle, $fallbackDate);
        $periodCode = $this->getPeriodString($periodDate, $cycle);

        return [
            'period' => $periodCode,
            'label' => $this->getPeriodLabel($periodDate, $cycle),
            'period_start' => $periodDate->copy()->startOf($this->getCarbonUnit($cycle)),
            'period_end' => $periodDate->copy()->endOf($this->getCarbonUnit($cycle)),
            'due_date' => $this->getDueDate($periodDate, $cycle),
        ];
    }

    public function parsePeriod(?string $period, string $cycle, ?Carbon $fallbackDate = null): Carbon
    {
        $fallback = ($fallbackDate ?: Carbon::now())->copy()->startOf($this->getCarbonUnit($cycle));

        if (!$period) {
            return $fallback;
        }

        try {
            return match ($cycle) {
                'daily' => $this->parseDailyPeriod($period, $fallback),
                'weekly' => $this->parseWeeklyPeriod($period, $fallback),
                'yearly' => $this->parseYearlyPeriod($period, $fallback),
                default => $this->parseMonthlyPeriod($period, $fallback),
            };
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function parseDailyPeriod(string $period, Carbon $fallback): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
            return Carbon::createFromFormat('Y-m-d', $period)->startOfDay();
        }

        return Carbon::parse($period)->startOfDay();
    }

    private function parseWeeklyPeriod(string $period, Carbon $fallback): Carbon
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $period, $matches)) {
            return Carbon::now()
                ->setISODate((int) $matches[1], (int) $matches[2])
                ->startOfWeek();
        }

        return Carbon::parse($period)->startOfWeek();
    }

    private function parseMonthlyPeriod(string $period, Carbon $fallback): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            return Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();
        }

        return Carbon::parse($period)->startOfMonth();
    }

    private function parseYearlyPeriod(string $period, Carbon $fallback): Carbon
    {
        if (preg_match('/^\d{4}$/', $period)) {
            return Carbon::createFromDate((int) $period, 1, 1)->startOfYear();
        }

        return Carbon::parse($period)->startOfYear();
    }
}

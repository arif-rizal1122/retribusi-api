<?php

namespace App\Services;

use App\Models\AssetItem;
use App\Models\AssetRental;
use Carbon\Carbon;

class AssetAvailabilityService
{
    /**
     * Mengambil status ketersediaan kalender harian untuk sebuah unit aset pada bulan tertentu.
     *
     * @param AssetItem $asset
     * @param string $month Format YYYY-MM
     * @return array
     */
    public function getMonthAvailability(AssetItem $asset, string $month): array
    {
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;
        $today = Carbon::today();

        $isOperational = (bool) (
            $asset->is_active &&
            $asset->kondisi !== 'Tidak Operasional' &&
            $asset->status_operasional === 'Operasional'
        );

        // Ambil rentang rental aktif yang dapat mempengaruhi bulan ini (termasuk buffer sebelum/sesudah bulan)
        $searchStart = $startOfMonth->copy()->subDays(14)->toDateString();
        $searchEnd = $endOfMonth->copy()->addDays(14)->toDateString();

        $rentals = AssetRental::where('asset_item_id', $asset->id)
            ->whereIn('status', ['Diajukan', 'Diverifikasi', 'Disetujui'])
            ->where(function ($query) use ($searchStart, $searchEnd) {
                $query->whereDate('tanggal_mulai', '<=', $searchEnd)
                    ->where(function ($sub) use ($searchStart) {
                        $sub->whereDate('tanggal_selesai', '>=', $searchStart)
                            ->orWhere(function ($s2) use ($searchStart) {
                                $s2->whereNull('tanggal_selesai')
                                    ->whereDate('tanggal_mulai', '>=', $searchStart);
                            });
                    });
            })
            ->get();

        $days = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $startOfMonth->copy()->day($day);
            $dateStr = $currentDate->toDateString();

            if (!$isOperational) {
                $days[$dateStr] = [
                    'status' => 'out_of_service',
                    'label' => 'Alat Tidak Operasional',
                ];
                continue;
            }

            if ($currentDate->lt($today)) {
                $days[$dateStr] = [
                    'status' => 'disabled_past',
                    'label' => 'Tanggal Lewat',
                ];
                continue;
            }

            $matchedBooked = null;
            $matchedPending = null;
            $matchedBuffer = null;

            foreach ($rentals as $rental) {
                $rentalStart = Carbon::parse($rental->tanggal_mulai)->startOfDay();
                $rentalEnd = $rental->tanggal_selesai
                    ? Carbon::parse($rental->tanggal_selesai)->startOfDay()
                    : $rentalStart->copy();
                $bufferDays = $rental->buffer_hari ?? $asset->buffer_default ?? 1;

                $bufferBeforeStart = $rentalStart->copy()->subDays($bufferDays);
                $bufferBeforeEnd = $rentalStart->copy()->subDay();
                $bufferAfterStart = $rentalEnd->copy()->addDay();
                $bufferAfterEnd = $rentalEnd->copy()->addDays($bufferDays);

                if ($currentDate->betweenIncluded($rentalStart, $rentalEnd)) {
                    if ($rental->status === 'Disetujui') {
                        $matchedBooked = $rental;
                        break;
                    } elseif (in_array($rental->status, ['Diajukan', 'Diverifikasi'])) {
                        $matchedPending = $rental;
                    }
                } elseif (
                    $currentDate->betweenIncluded($bufferBeforeStart, $bufferBeforeEnd) ||
                    $currentDate->betweenIncluded($bufferAfterStart, $bufferAfterEnd)
                ) {
                    $matchedBuffer = $rental;
                }
            }

            if ($matchedBooked) {
                $days[$dateStr] = [
                    'status' => 'booked',
                    'label' => 'Disewa (Disetujui)',
                    'rental_code' => $matchedBooked->rental_code,
                ];
            } elseif ($matchedPending) {
                $days[$dateStr] = [
                    'status' => 'pending_verification',
                    'label' => 'Dalam Verifikasi Petugas',
                    'rental_code' => $matchedPending->rental_code,
                ];
            } elseif ($matchedBuffer) {
                $days[$dateStr] = [
                    'status' => 'buffer',
                    'label' => 'Jeda Pemeliharaan / Mobilisasi',
                    'rental_code' => $matchedBuffer->rental_code,
                ];
            } else {
                $days[$dateStr] = [
                    'status' => 'available',
                    'label' => 'Tersedia',
                ];
            }
        }

        $suggestedNextSlot = $isOperational
            ? $this->findNextAvailableDate($asset->id, $today, 3)
            : null;

        return [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'asset_code' => $asset->code ?? '',
            'condition' => $asset->kondisi,
            'is_operational' => $isOperational,
            'month' => $month,
            'days' => $days,
            'suggested_next_slot' => $suggestedNextSlot,
        ];
    }

    /**
     * Mencari slot tanggal terdekat yang tersedia (termasuk buffer) dengan durasi tertentu.
     *
     * @param int $assetId
     * @param Carbon|null $from
     * @param int $durationDays
     * @return array|null ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
     */
    public function findNextAvailableDate(int $assetId, ?Carbon $from = null, int $durationDays = 3): ?array
    {
        $asset = AssetItem::find($assetId);
        if (!$asset || !$asset->is_active || $asset->kondisi === 'Tidak Operasional' || $asset->status_operasional !== 'Operasional') {
            return null;
        }

        $durationDays = max(1, $durationDays);
        $today = Carbon::today();
        $startDate = $from ? $from->copy()->startOfDay() : $today->copy();
        if ($startDate->lt($today)) {
            $startDate = $today->copy();
        }

        $buffer = $asset->buffer_default ?? 1;

        // Pencarian hingga 90 hari ke depan
        for ($i = 0; $i < 90; $i++) {
            $candidateStart = $startDate->copy()->addDays($i);
            $candidateEnd = $candidateStart->copy()->addDays($durationDays - 1);

            $blockedStart = $candidateStart->copy()->subDays($buffer)->toDateString();
            $blockedEnd = $candidateEnd->copy()->addDays($buffer)->toDateString();

            $hasConflict = AssetRental::where('asset_item_id', $assetId)
                ->whereIn('status', ['Diajukan', 'Diverifikasi', 'Disetujui'])
                ->where(function ($query) use ($blockedStart, $blockedEnd) {
                    $query->whereDate('tanggal_mulai', '<=', $blockedEnd)
                        ->where(function ($sub) use ($blockedStart) {
                            $sub->whereDate('tanggal_selesai', '>=', $blockedStart)
                                ->orWhere(function ($s2) use ($blockedStart) {
                                    $s2->whereNull('tanggal_selesai')
                                        ->whereDate('tanggal_mulai', '>=', $blockedStart);
                                });
                        });
                })
                ->exists();

            if (!$hasConflict) {
                return [
                    'start' => $candidateStart->toDateString(),
                    'end' => $candidateEnd->toDateString(),
                ];
            }
        }

        return null;
    }
}

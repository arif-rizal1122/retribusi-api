<?php

namespace App\Http\Controllers\Api\V1\Pupr;

use App\Http\Controllers\Controller;
use App\Models\AssetItem;
use App\Services\AssetAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssetCalendarController extends Controller
{
    protected AssetAvailabilityService $availabilityService;

    public function __construct(AssetAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Dapatkan status ketersediaan kalender booking armada (termasuk buffer H-1/H+1)
     */
    public function getCalendar(Request $request, $itemId)
    {
        $asset = AssetItem::findOrFail($itemId);
        $month = $request->query('month', date('Y-m'));

        $data = $this->availabilityService->getMonthAvailability($asset, $month);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Periksa ketersediaan tanggal booking dengan validasi buffer maintenance/mobilisasi
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'asset_item_id' => 'required|integer|exists:asset_items,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $asset = AssetItem::findOrFail($validated['asset_item_id']);
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->startOfDay();
        $buffer = $asset->buffer_default ?? 1;

        $checkStart = $startDate->copy()->subDays($buffer)->toDateString();
        $checkEnd = $endDate->copy()->addDays($buffer)->toDateString();

        $conflict = \App\Models\AssetRental::where('asset_item_id', $asset->id)
            ->whereIn('status', ['Diajukan', 'Diverifikasi', 'Disetujui'])
            ->where(function ($q) use ($checkStart, $checkEnd) {
                $q->whereDate('tanggal_mulai', '<=', $checkEnd)
                  ->where(function ($sub) use ($checkStart) {
                      $sub->whereDate('tanggal_selesai', '>=', $checkStart)
                          ->orWhere(function ($s2) use ($checkStart) {
                              $s2->whereNull('tanggal_selesai')
                                 ->whereDate('tanggal_mulai', '>=', $checkStart);
                          });
                  });
            })
            ->first();

        if ($conflict) {
            $nextSlot = $this->availabilityService->findNextAvailableDate(
                $asset->id,
                $startDate,
                $startDate->diffInDays($endDate) + 1
            );

            return response()->json([
                'status' => 'unavailable',
                'message' => 'Tanggal yang dipilih bertabrakan dengan jadwal sewa lain atau jeda buffer pemeliharaan H-1/H+1.',
                'conflict' => [
                    'rental_code' => $conflict->rental_code,
                    'status' => $conflict->status,
                    'start_date' => $conflict->tanggal_mulai,
                    'end_date' => $conflict->tanggal_selesai,
                ],
                'suggested_next_slot' => $nextSlot,
            ], 422);
        }

        return response()->json([
            'status' => 'available',
            'message' => 'Slot waktu armada tersedia dan buffer pemeliharaan aman.',
            'data' => [
                'asset_id' => $asset->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'buffer_days' => $buffer,
                'buffer_period' => [
                    'pre_buffer' => $startDate->copy()->subDays($buffer)->toDateString(),
                    'post_buffer' => $endDate->copy()->addDays($buffer)->toDateString(),
                ],
            ],
        ]);
    }
}

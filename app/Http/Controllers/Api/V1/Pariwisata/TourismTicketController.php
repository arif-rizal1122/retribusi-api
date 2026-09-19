<?php

namespace App\Http\Controllers\Api\V1\Pariwisata;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tiket objek wisata — booking ala bioskop, check-in rombongan, keringanan Pasal 106.
 * OPD 16 (Dinas Pariwisata dan Kebudayaan Kota Baubau).
 */
class TourismTicketController extends Controller
{
    private const OPD_ID = 16;

    private const GROUP_MIN_PAX = 25;

    private const GROUP_DISCOUNT_PERCENT = 10;

    /** Daftar destinasi + matriks tarif + kuota hari ini + aset sewa. */
    public function getCatalog(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());

        $destinations = DB::table('tourism_destinations')
            ->where('opd_id', self::OPD_ID)
            ->where('is_active', 1)
            ->get();

        $out = [];

        foreach ($destinations as $d) {
            $rates = RetributionRate::withoutGlobalScopes()
                ->where('opd_id', self::OPD_ID)
                ->whereIn('retribution_classification_id', function ($q) use ($d) {
                    $q->select('id')->from('retribution_classifications')
                        ->where('opd_id', self::OPD_ID)
                        ->where('code', 'like', $d->code.'-%');
                })
                ->get()
                ->map(function ($r) {
                    $classCode = DB::table('retribution_classifications')
                        ->where('id', $r->retribution_classification_id)->value('code');
                    $parts = explode('-', (string) $classCode);

                    return [
                        'classification_id' => $r->retribution_classification_id,
                        'visitor_category' => strtolower(end($parts)),
                        'label' => $r->name,
                        'amount' => (float) $r->amount,
                        'unit' => $r->unit,
                    ];
                })
                ->values();

            $quota = DB::table('tourism_quotas')
                ->where('tourism_destination_id', $d->id)
                ->where('quota_date', $date)
                ->get();

            $booked = $quota->sum('booked');
            $capacity = $quota->max('capacity') ?: $d->daily_capacity;

            $out[] = [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'slug' => $d->slug,
                'description' => $d->description,
                'open_time' => $d->open_time,
                'close_time' => $d->close_time,
                'requires_offline_mode' => (bool) $d->requires_offline_mode,
                'is_open' => (bool) $d->is_open,
                'quota' => [
                    'date' => $date,
                    'capacity' => (int) $capacity,
                    'booked' => (int) $booked,
                    'remaining' => max(0, (int) $capacity - (int) $booked),
                ],
                'rates' => $rates,
            ];
        }

        $addons = DB::table('asset_items')
            ->where('opd_id', self::OPD_ID)
            ->where('is_active', 1)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'tarif' => (float) $a->tarif_default,
                'satuan' => $a->satuan_tarif,
                'lokasi' => $a->lokasi,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'destinations' => $out,
                'addons' => $addons,
                'group_rules' => [
                    'min_pax' => self::GROUP_MIN_PAX,
                    'discount_percent' => self::GROUP_DISCOUNT_PERCENT,
                ],
            ],
        ]);
    }

    /**
     * Pemesanan tiket multi-item (ala bioskop).
     * Body: destination_id, visit_date, session_time, items[], addons[].
     */
    public function order(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_id' => 'required|integer',
            'visit_date' => 'required|date|after_or_equal:today',
            'session_time' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.classification_id' => 'required|integer',
            'items.*.pax' => 'required|integer|min:1',
            'addons' => 'nullable|array',
            'addons.*.asset_item_id' => 'required_with:addons|integer',
            'addons.*.qty' => 'required_with:addons|integer|min:1',
        ]);

        $destination = DB::table('tourism_destinations')
            ->where('id', $validated['destination_id'])
            ->where('opd_id', self::OPD_ID)
            ->first();

        if (! $destination) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);
        }

        $totalPax = array_sum(array_column($validated['items'], 'pax'));
        $ticketType = $totalPax >= self::GROUP_MIN_PAX ? 'group' : 'individual';
        $discountPercent = $ticketType === 'group' ? self::GROUP_DISCOUNT_PERCENT : 0;

        $lines = [];
        $subtotal = 0;

        foreach ($validated['items'] as $item) {
            $rate = RetributionRate::withoutGlobalScopes()
                ->where('opd_id', self::OPD_ID)
                ->where('retribution_classification_id', $item['classification_id'])
                ->where('is_active', 1)
                ->first();

            if (! $rate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif tidak ditemukan untuk classification_id '.$item['classification_id'].'.',
                ], 422);
            }

            $lineTotal = (float) $rate->amount * (int) $item['pax'];
            $subtotal += $lineTotal;

            $lines[] = [
                'classification_id' => $item['classification_id'],
                'label' => $rate->name,
                'pax' => (int) $item['pax'],
                'unit_amount' => (float) $rate->amount,
                'line_total' => $lineTotal,
            ];
        }

        foreach ($validated['addons'] ?? [] as $addon) {
            $asset = DB::table('asset_items')
                ->where('id', $addon['asset_item_id'])
                ->where('opd_id', self::OPD_ID)
                ->first();

            if (! $asset) {
                continue;
            }

            $lineTotal = (float) $asset->tarif_default * (int) $addon['qty'];
            $subtotal += $lineTotal;

            $lines[] = [
                'asset_item_id' => $asset->id,
                'label' => $asset->name.' (sewa '.$asset->satuan_tarif.')',
                'pax' => (int) $addon['qty'],
                'unit_amount' => (float) $asset->tarif_default,
                'line_total' => $lineTotal,
            ];
        }

        $discountAmount = round($subtotal * $discountPercent / 100, 2);
        $total = $subtotal - $discountAmount;

        $taxpayerId = optional($request->user())->id;

        $bill = DB::transaction(function () use (
            $validated, $destination, $lines, $totalPax, $ticketType,
            $discountPercent, $subtotal, $discountAmount, $total, $taxpayerId
        ) {
            $typeId = DB::table('retribution_types')
                ->where('opd_id', self::OPD_ID)->where('is_active', 1)->value('id');

            $billId = DB::table('bills')->insertGetId([
                'user_id' => $taxpayerId,
                'taxpayer_id' => $taxpayerId,
                'opd_id' => self::OPD_ID,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $lines[0]['classification_id'] ?? null,
                'bill_number' => 'PAR-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'amount' => $total,
                'status' => 'pending',
                'due_date' => $validated['visit_date'],
                'tourism_destination_id' => $destination->id,
                'ticket_type' => $ticketType,
                'visitor_category' => null,
                'pax_count' => $totalPax,
                'pax_checked_in' => 0,
                'visit_date' => $validated['visit_date'],
                'session_time' => $validated['session_time'] ?? 'all',
                'legal_discount_percent' => $discountPercent,
                'master_qr_token' => $ticketType === 'group' ? Str::uuid()->toString() : null,
                'metadata' => json_encode([
                    'lines' => $lines,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Reservasi kuota
            DB::table('tourism_quotas')->updateOrInsert(
                [
                    'tourism_destination_id' => $destination->id,
                    'quota_date' => $validated['visit_date'],
                    'session_time' => $validated['session_time'] ?? 'all',
                ],
                [
                    'capacity' => $destination->session_quota ?: $destination->daily_capacity,
                    'booked' => DB::raw('COALESCE(booked, 0) + '.$totalPax),
                    'checked_in' => DB::raw('COALESCE(checked_in, 0)'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return Bill::withoutGlobalScopes()->find($billId);
        });

        return response()->json([
            'success' => true,
            'message' => $ticketType === 'group'
                ? 'Pemesanan rombongan berhasil. Master QR diterbitkan untuk '.$totalPax.' orang.'
                : 'Pemesanan tiket berhasil.',
            'data' => [
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'ticket_type' => $ticketType,
                'pax_count' => $totalPax,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total' => (float) $bill->amount,
                'master_qr_token' => $bill->master_qr_token,
                'lines' => $lines,
            ],
        ], 201);
    }

    /** Check-in bertahap di gerbang (headcount scanner rombongan). */
    public function scanCheckIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_number' => 'required_without:master_qr_token|string',
            'master_qr_token' => 'required_without:bill_number|string',
            'headcount' => 'required|integer|min:1',
        ]);

        $bill = Bill::withoutGlobalScopes()
            ->where('opd_id', self::OPD_ID)
            ->when(
                ! empty($validated['master_qr_token']),
                fn ($q) => $q->where('master_qr_token', $validated['master_qr_token']),
                fn ($q) => $q->where('bill_number', $validated['bill_number'])
            )
            ->first();

        if (! $bill) {
            return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan.'], 404);
        }

        if ($bill->status !== 'paid' && $bill->status !== 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Tiket belum lunas. Status: '.$bill->status,
            ], 422);
        }

        $remaining = (int) $bill->pax_count - (int) $bill->pax_checked_in;

        if ($validated['headcount'] > $remaining) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah melebihi sisa pax. Sisa: '.$remaining,
                'data' => ['pax_count' => (int) $bill->pax_count, 'pax_checked_in' => (int) $bill->pax_checked_in, 'remaining' => $remaining],
            ], 422);
        }

        $newCheckedIn = (int) $bill->pax_checked_in + (int) $validated['headcount'];

        DB::transaction(function () use ($bill, $newCheckedIn, $validated) {
            DB::table('bills')->where('id', $bill->id)->update([
                'pax_checked_in' => $newCheckedIn,
                'updated_at' => now(),
            ]);

            if ($bill->tourism_destination_id) {
                DB::table('tourism_quotas')
                    ->where('tourism_destination_id', $bill->tourism_destination_id)
                    ->where('quota_date', $bill->visit_date)
                    ->where('session_time', $bill->session_time ?? 'all')
                    ->update(['checked_in' => DB::raw('COALESCE(checked_in, 0) + '.(int) $validated['headcount'])]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Check-in '.$validated['headcount'].' orang tercatat.',
            'data' => [
                'bill_number' => $bill->bill_number,
                'pax_count' => (int) $bill->pax_count,
                'pax_checked_in' => $newCheckedIn,
                'remaining' => (int) $bill->pax_count - $newCheckedIn,
                'complete' => $newCheckedIn >= (int) $bill->pax_count,
            ],
        ]);
    }

    /**
     * Keringanan legal sewa kios (Pasal 106 Perda 1/2024).
     * Tarif dasar Perda TIDAK diubah; diskon tercatat di tourism_legal_waivers.
     */
    public function applyKioskRelief(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_id' => 'required|integer',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'decree_number' => 'required|string|max:255',
            'reason' => 'nullable|string',
        ]);

        $bill = Bill::withoutGlobalScopes()
            ->where('id', $validated['bill_id'])
            ->where('opd_id', self::OPD_ID)
            ->first();

        if (! $bill) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
        }

        $base = (float) $bill->amount;
        $final = round($base * (100 - (float) $validated['discount_percent']) / 100, 2);

        $result = DB::transaction(function () use ($bill, $validated, $base, $final) {
            $waiverId = DB::table('tourism_legal_waivers')->insertGetId([
                'opd_id' => self::OPD_ID,
                'bill_id' => $bill->id,
                'legal_basis' => 'Pasal 106 Perda Kota Baubau No. 1 Tahun 2024',
                'base_amount' => $base,
                'discount_percent' => $validated['discount_percent'],
                'final_amount' => $final,
                'decree_number' => $validated['decree_number'],
                'reason' => $validated['reason'] ?? null,
                'requested_by_user_id' => optional(request()->user())->id,
                'status' => 'Disetujui',
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('bills')->where('id', $bill->id)->update([
                'amount' => $final,
                'legal_discount_percent' => $validated['discount_percent'],
                'legal_waiver_ref' => $validated['decree_number'],
                'updated_at' => now(),
            ]);

            return $waiverId;
        });

        return response()->json([
            'success' => true,
            'message' => 'Keringanan legal diterbitkan. Tarif dasar Perda tetap Rp '.number_format($base, 0, ',', '.').'.',
            'data' => [
                'waiver_id' => $result,
                'bill_id' => $bill->id,
                'base_amount' => $base,
                'discount_percent' => (float) $validated['discount_percent'],
                'final_amount' => $final,
                'legal_basis' => 'Pasal 106 Perda Kota Baubau No. 1 Tahun 2024',
                'decree_number' => $validated['decree_number'],
            ],
        ], 201);
    }
}

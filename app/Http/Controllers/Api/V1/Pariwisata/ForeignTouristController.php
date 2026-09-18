<?php

namespace App\Http\Controllers\Api\V1\Pariwisata;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Portal wisatawan asing — guest checkout tanpa NIK/login, verifikasi paspor.
 * Pembayaran lintas negara (cross-border QRIS / kartu internasional) menunggu
 * kesiapan PKS bank; endpoint ini sudah menyiapkan struktur datanya.
 *
 * REPLACE_VISIT_ORIGIN: origin portal publik dari subdomain mpad (bukan subdomain baru).
 */
class ForeignTouristController extends Controller
{
    private const OPD_ID = 16;

    /** Pemesanan tamu tanpa login — cukup nama, paspor, negara, email/WA. */
    public function guestCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'passport_number' => 'required|string|max:50',
            'nationality' => 'required|string|size:3',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'destination_id' => 'required|integer',
            'visit_date' => 'required|date|after_or_equal:today',
            'session_time' => 'nullable|string|max:20',
            'pax' => 'required|integer|min:1|max:50',
            'addons' => 'nullable|array',
            'addons.*.asset_item_id' => 'required_with:addons|integer',
            'addons.*.qty' => 'required_with:addons|integer|min:1',
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau nomor WhatsApp wajib diisi untuk pengiriman e-ticket.',
            ], 422);
        }

        $destination = DB::table('tourism_destinations')
            ->where('id', $validated['destination_id'])
            ->where('opd_id', self::OPD_ID)
            ->first();

        if (! $destination) {
            return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);
        }

        // Tarif WNA untuk destinasi ini
        $classCode = $destination->code.'-FOREIGN';
        $rate = DB::table('retribution_rates')
            ->join('retribution_classifications', 'retribution_rates.retribution_classification_id', '=', 'retribution_classifications.id')
            ->where('retribution_classifications.code', $classCode)
            ->where('retribution_rates.opd_id', self::OPD_ID)
            ->where('retribution_rates.is_active', 1)
            ->select('retribution_rates.amount', 'retribution_rates.name', 'retribution_rates.id as rate_id')
            ->first();

        if (! $rate) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif wisatawan mancanegara belum tersedia untuk destinasi ini.',
            ], 422);
        }

        $pax = (int) $validated['pax'];
        $subtotal = (float) $rate->amount * $pax;

        $addonLines = [];
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
            $addonLines[] = [
                'asset_item_id' => $asset->id,
                'name' => $asset->name,
                'qty' => (int) $addon['qty'],
                'unit_amount' => (float) $asset->tarif_default,
                'line_total' => $lineTotal,
            ];
        }

        $bookingRef = 'FOR-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        $result = DB::transaction(function () use ($validated, $destination, $rate, $pax, $subtotal, $bookingRef, $addonLines) {
            $typeId = DB::table('retribution_types')
                ->where('opd_id', self::OPD_ID)->where('is_active', 1)->value('id');

            $classificationId = DB::table('retribution_classifications')
                ->where('opd_id', self::OPD_ID)->where('code', $destination->code.'-FOREIGN')->value('id');

            $billId = DB::table('bills')->insertGetId([
                'user_id' => null,
                'taxpayer_id' => null,
                'opd_id' => self::OPD_ID,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $classificationId,
                'bill_number' => $bookingRef,
                'amount' => $subtotal,
                'status' => 'pending',
                'due_date' => $validated['visit_date'],
                'tourism_destination_id' => $destination->id,
                'ticket_type' => $pax >= 25 ? 'group' : 'individual',
                'visitor_category' => 'foreign',
                'pax_count' => $pax,
                'pax_checked_in' => 0,
                'visit_date' => $validated['visit_date'],
                'session_time' => $validated['session_time'] ?? 'all',
                'nationality' => strtoupper($validated['nationality']),
                'passport_number' => $validated['passport_number'],
                'master_qr_token' => Str::uuid()->toString(),
                'metadata' => json_encode([
                    'guest' => [
                        'full_name' => $validated['full_name'],
                        'email' => $validated['email'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                    ],
                    'addons' => $addonLines,
                    'payment_channel' => 'cross_border_pending',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('tourism_quotas')->updateOrInsert(
                [
                    'tourism_destination_id' => $destination->id,
                    'quota_date' => $validated['visit_date'],
                    'session_time' => $validated['session_time'] ?? 'all',
                ],
                [
                    'capacity' => $destination->session_quota ?: $destination->daily_capacity,
                    'booked' => DB::raw('COALESCE(booked, 0) + '.$pax),
                    'checked_in' => DB::raw('COALESCE(checked_in, 0)'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return Bill::withoutGlobalScopes()->find($billId);
        });

        return response()->json([
            'success' => true,
            'message' => 'Guest booking created. E-ticket will be sent to '.($validated['email'] ?? $validated['phone']).'.',
            'data' => [
                'booking_ref' => $bookingRef,
                'bill_id' => $result->id,
                'destination' => $destination->name,
                'visit_date' => $validated['visit_date'],
                'session_time' => $validated['session_time'] ?? 'all',
                'pax' => $pax,
                'unit_amount' => (float) $rate->amount,
                'total' => $subtotal,
                'currency' => 'IDR',
                'master_qr_token' => $result->master_qr_token,
                'payment_channels' => [
                    ['code' => 'cross_border_qris', 'label' => 'QRIS Cross-Border (MY/SG/TH)', 'available' => false, 'note' => 'Menunggu PKS bank'],
                    ['code' => 'international_card', 'label' => 'Visa / Mastercard', 'available' => false, 'note' => 'Menunggu acquirer'],
                ],
                'addons' => $addonLines,
            ],
        ], 201);
    }

    /** Generator tautan khusus untuk agen travel, kapal pesiar, dan hotel. */
    public function generateBookingLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner_name' => 'required|string|max:150',
            'partner_type' => 'required|in:travel_agency,cruise,hotel,other',
            'destination_id' => 'nullable|integer',
            'max_pax' => 'nullable|integer|min:1|max:500',
            'valid_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validated['destination_id'] ?? null) {
            $exists = DB::table('tourism_destinations')
                ->where('id', $validated['destination_id'])
                ->where('opd_id', self::OPD_ID)
                ->exists();

            if (! $exists) {
                return response()->json(['success' => false, 'message' => 'Destinasi tidak ditemukan.'], 404);
            }
        }

        $token = Str::random(32);
        $validDays = $validated['valid_days'] ?? 30;

        $payload = [
            'partner_name' => $validated['partner_name'],
            'partner_type' => $validated['partner_type'],
            'destination_id' => $validated['destination_id'] ?? null,
            'max_pax' => $validated['max_pax'] ?? 50,
            'created_by_user_id' => optional($request->user())->id,
            'opd_id' => self::OPD_ID,
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays($validDays)->toIso8601String(),
        ];

        Cache::put('tourism_partner_link:'.$token, $payload, now()->addDays($validDays));

        $origin = rtrim(config('app.frontend_url', config('app.url')), '/');
        $url = $origin.'/tourism/en?ref='.$token;

        return response()->json([
            'success' => true,
            'message' => 'Tautan kemitraan dibuat, berlaku '.$validDays.' hari.',
            'data' => [
                'token' => $token,
                'url' => $url,
                'partner_name' => $validated['partner_name'],
                'partner_type' => $validated['partner_type'],
                'max_pax' => $payload['max_pax'],
                'expires_at' => $payload['expires_at'],
            ],
        ], 201);
    }

    /** Cek status tiket berdasarkan nomor tiket atau token QR. */
    public function checkTicketStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_number' => 'required_without:master_qr_token|string',
            'master_qr_token' => 'required_without:bill_number|string',
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
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        $destination = $bill->tourism_destination_id
            ? DB::table('tourism_destinations')->where('id', $bill->tourism_destination_id)->first()
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'bill_number' => $bill->bill_number,
                'status' => $bill->status,
                'paid' => in_array($bill->status, ['paid', 'lunas'], true),
                'destination' => $destination->name ?? null,
                'visit_date' => $bill->visit_date,
                'session_time' => $bill->session_time,
                'visitor_category' => $bill->visitor_category,
                'pax_count' => (int) $bill->pax_count,
                'pax_checked_in' => (int) $bill->pax_checked_in,
                'remaining' => (int) $bill->pax_count - (int) $bill->pax_checked_in,
                'total' => (float) $bill->amount,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DamkarVerification;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * M43 (Fase 3-4) — Verifikasi teknis proteksi kebakaran oleh Damkar.
 *
 * Damkar (opd_id=13) = verifikator teknis syarat PBG, BUKAN pemungut retribusi.
 * Endpoint hanya untuk user dengan canVerifyFireProtection() == true.
 */
class DamkarVerificationController extends Controller
{
    /**
     * Daftar objek verifikasi (default: menunggu verifikasi).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! ($user instanceof User) || ! $user->canVerifyFireProtection()) {
            return response()->json([
                'message' => 'Forbidden: akses khusus verifikator teknis Damkar.',
            ], 403);
        }

        $status = $request->query('status', DamkarVerification::STATUS_PENDING);
        if (! in_array($status, [DamkarVerification::STATUS_PENDING, DamkarVerification::STATUS_APPROVED, DamkarVerification::STATUS_REJECTED], true)) {
            $status = DamkarVerification::STATUS_PENDING;
        }

        $items = DamkarVerification::where('status', $status)
            ->orderByDesc('id')
            ->limit(min((int) $request->query('limit', 50), 200))
            ->get(['id', 'tax_object_id', 'building_name', 'owner_name', 'address', 'status', 'notes', 'rekomendasi_teknis', 'verified_by', 'verified_at']);

        return response()->json([
            'data' => $items,
            'meta' => ['status' => $status, 'total' => $items->count()],
        ]);
    }

    /**
     * Keputusan verifikasi: approved | rejected + catatan.
     */
    public function decision(Request $request, $id)
    {
        $user = $request->user();

        if (! $user || ! ($user instanceof User) || ! $user->canVerifyFireProtection()) {
            return response()->json([
                'message' => 'Forbidden: akses khusus verifikator teknis Damkar.',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:2000',
            'rekomendasi_teknis' => 'nullable|string|max:2000',
        ]);

        $item = DamkarVerification::find($id);
        if (! $item) {
            return response()->json(['message' => 'Data verifikasi tidak ditemukan.'], 404);
        }

        $item->status = $request->status;
        if ($request->filled('notes')) {
            $item->notes = $request->notes;
        }
        if ($request->filled('rekomendasi_teknis')) {
            $item->rekomendasi_teknis = $request->rekomendasi_teknis;
        }
        $item->verified_by = $user->id;
        $item->verified_at = now();
        $item->save();

        $label = $request->status === 'approved'
            ? 'Rekomendasi teknis proteksi kebakaran telah diterbitkan.'
            : 'Verifikasi proteksi kebakaran ditolak. Catatan telah disimpan.';

        return response()->json([
            'message' => $label,
            'data' => $item->fresh(),
        ]);
    }
}

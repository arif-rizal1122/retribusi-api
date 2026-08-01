<?php

namespace App\Http\Controllers;

use App\Models\QrToken;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * QR "Saya" (customer-presented & merchant-presented) — M18 F3/F2
 * - citizenMyQr: QR milik WP (di-scan petugas)
 * - officerMyQr: QR milik petugas (di-scan WP)
 * - verifyTaxpayer: resolve QR WP (publik, dipakai petugas)
 * - verifyOfficer: resolve QR petugas (publik, dipakai WP)
 */
class QrController extends Controller
{
    private const BASE_URL = 'https://apimpad.baubaukota.go.id';

    private function getOrCreateToken(string $modelType, int $modelId): string
    {
        $existing = QrToken::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->first();

        if ($existing) {
            return $existing->token;
        }

        do {
            $token = Str::random(32);
        } while (QrToken::where('token', $token)->exists());

        QrToken::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'token' => $token,
        ]);

        return $token;
    }

    private function qrPayload(string $kind, string $token): array
    {
        $url = self::BASE_URL . '/api/verify/' . $kind . '/' . $token;
        $image = null;
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(400)
                ->margin(2)
                ->generate($url);
            $image = 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            $image = null;
        }

        return [
            'qr_url' => $url,
            'qr_image' => $image,
            'token' => $token,
        ];
    }

    /**
     * GET /api/citizen/my-qr — QR milik wajib pajak (customer-presented)
     */
    public function citizenMyQr(Request $request)
    {
        $user = $request->user();

        if (!$user instanceof Taxpayer) {
            return response()->json(['message' => 'Akses khusus wajib pajak'], 403);
        }

        $token = $this->getOrCreateToken(Taxpayer::class, $user->id);

        return response()->json([
            'success' => true,
            'name' => $user->name,
            'taxpayer_id' => $user->id,
            ...$this->qrPayload('taxpayer', $token),
        ]);
    }

    /**
     * GET /api/officer/my-qr — QR milik petugas (merchant-presented)
     */
    public function officerMyQr(Request $request)
    {
        $user = $request->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'Akses khusus petugas/admin'], 403);
        }

        $token = $this->getOrCreateToken(User::class, $user->id);

        return response()->json([
            'success' => true,
            'name' => $user->name,
            'officer_id' => $user->id,
            'role' => $user->role,
            ...$this->qrPayload('officer', $token),
        ]);
    }

    /**
     * GET /api/verify/taxpayer/{token} — resolve QR WP (publik, dipakai petugas)
     */
    public function verifyTaxpayer($token)
    {
        $qr = QrToken::where('token', $token)
            ->where('model_type', Taxpayer::class)
            ->first();

        if (!$qr) {
            return response()->json(['success' => false, 'message' => 'QR tidak valid'], 404);
        }

        $taxpayer = Taxpayer::find($qr->model_id);

        if (!$taxpayer) {
            return response()->json(['success' => false, 'message' => 'Wajib pajak tidak ditemukan'], 404);
        }

        // Tagihan aktif (pending/overdue) milik WP ini
        $bills = \App\Models\Bill::where('taxpayer_id', $taxpayer->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get(['id', 'bill_number', 'period', 'status', 'total_amount', 'amount', 'due_date']);

        return response()->json([
            'success' => true,
            'taxpayer' => [
                'id' => $taxpayer->id,
                'name' => $taxpayer->name,
                'nik' => $taxpayer->nik,
                'npwpd' => $taxpayer->npwpd ?? null,
            ],
            'bills' => $bills,
        ]);
    }

    /**
     * GET /api/verify/officer/{token} — resolve QR petugas (publik, dipakai WP)
     */
    public function verifyOfficer($token)
    {
        $qr = QrToken::where('token', $token)
            ->where('model_type', User::class)
            ->first();

        if (!$qr) {
            return response()->json(['success' => false, 'message' => 'QR tidak valid'], 404);
        }

        $user = User::find($qr->model_id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Petugas tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'officer' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'opd' => $user->opd?->name ?? null,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Taxpayer;
use App\Services\WaGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CitizenPinController extends Controller
{
    /**
     * Weak/trivial PIN patterns to reject
     */
    protected array $weakPins = [
        '000000', '111111', '222222', '333333', '444444',
        '555555', '666666', '777777', '888888', '999999',
        '123456', '234567', '345678', '456789', '567890',
        '654321', '543210', '987654', '876543', '765432',
    ];

    /**
     * Setup initial PIN or change existing PIN.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun warga/wajib pajak.'], 403);
        }

        $rules = [
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'pin_confirmation' => ['required', 'same:pin'],
        ];

        // If user already configured a PIN, require current_pin
        if (! empty($user->pin_hash)) {
            $rules['current_pin'] = ['required', 'string', 'size:6'];
        }

        $request->validate($rules, [
            'pin.regex' => 'PIN harus berupa 6 angka numerik.',
            'pin_confirmation.same' => 'Konfirmasi PIN tidak cocok.',
            'current_pin.required' => 'PIN saat ini wajib diisi untuk mengubah PIN.',
        ]);

        if (! empty($user->pin_hash)) {
            if (! Hash::check($request->current_pin, $user->pin_hash)) {
                return response()->json([
                    'message' => 'PIN saat ini salah.',
                    'errors' => ['current_pin' => ['PIN saat ini salah.']],
                ], 422);
            }
        }

        if (in_array($request->pin, $this->weakPins, true)) {
            return response()->json([
                'message' => 'PIN terlalu mudah ditebak. Hindari kombinasi angka berulang atau berurutan.',
                'errors' => ['pin' => ['PIN terlalu mudah ditebak. Gunakan kombinasi angka lain.']],
            ], 422);
        }

        $user->pin_hash = Hash::make($request->pin);
        $user->pin_set_at = now();
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'PIN keamanan 6-digit berhasil disimpan.',
            'has_pin' => true,
        ]);
    }

    /**
     * Verify citizen PIN for sensitive transactions/approvals.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun warga/wajib pajak.'], 403);
        }

        if (empty($user->pin_hash)) {
            return response()->json([
                'message' => 'Anda belum mengatur PIN keamanan. Silakan buat PIN terlebih dahulu.',
                'has_pin' => false,
            ], 400);
        }

        // Check if currently locked
        if ($user->pin_locked_until && now()->lt($user->pin_locked_until)) {
            $diffSeconds = now()->diffInSeconds($user->pin_locked_until);
            $minutes = max(1, (int) ceil($diffSeconds / 60));

            return response()->json([
                'message' => "Percobaan PIN terkunci sementara karena salah 3 kali berturut-turut. Silakan coba lagi dalam {$minutes} menit atau gunakan menu Lupa PIN.",
                'locked' => true,
                'remaining_minutes' => $minutes,
            ], 423);
        }

        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.regex' => 'PIN harus berupa 6 angka.',
        ]);

        if (Hash::check($request->pin, $user->pin_hash)) {
            // Reset attempts on successful verification
            $user->pin_failed_attempts = 0;
            $user->pin_locked_until = null;
            $user->save();

            return response()->json([
                'success' => true,
                'verified' => true,
                'message' => 'Verifikasi PIN berhasil.',
            ]);
        }

        // Incorrect PIN - increment counter
        $attempts = ($user->pin_failed_attempts ?? 0) + 1;
        $user->pin_failed_attempts = $attempts;

        if ($attempts >= 3) {
            $user->pin_locked_until = now()->addMinutes(15);
            $user->save();

            return response()->json([
                'message' => 'PIN salah 3 kali berturut-turut. Akun dikunci dari verifikasi PIN selama 15 menit.',
                'locked' => true,
                'remaining_minutes' => 15,
            ], 423);
        }

        $user->save();
        $remaining = 3 - $attempts;

        return response()->json([
            'message' => "PIN tidak cocok. Kesempatan tersisa {$remaining} kali.",
            'remaining_attempts' => $remaining,
            'errors' => ['pin' => ["PIN salah. Sisa percobaan: {$remaining}"]],
        ], 422);
    }

    /**
     * Request OTP to reset forgotten PIN.
     */
    public function requestResetOtp(Request $request, WaGatewayService $waGateway): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun warga/wajib pajak.'], 403);
        }

        $phone = $user->phone;
        $email = $user->email;

        if (empty($phone) && empty($email)) {
            return response()->json([
                'message' => 'Akun Anda belum memiliki nomor WhatsApp atau Email untuk menerima OTP pemulihan.',
            ], 422);
        }

        $otp = sprintf('%06d', mt_rand(100000, 999999));
        Cache::put("pin_reset_otp_{$user->id}", $otp, now()->addMinutes(5));

        $sentVia = [];

        // 1. Send via WhatsApp if phone available
        if (! empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62'.substr($cleanPhone, 1);
            }
            $message = "Halo! Ini adalah kode OTP Reset PIN Keamanan M-PAD Baubau Anda: *{$otp}*\n\nBerlaku selama 5 menit. JANGAN BERIKAN kode ini kepada siapa pun.";
            $waGateway->sendMessage($cleanPhone, $message);
            $sentVia[] = 'WhatsApp ('.substr($cleanPhone, 0, 4).'****'.substr($cleanPhone, -3).')';
        }

        // 2. Also send via Email if available
        if (! empty($email)) {
            try {
                Mail::raw("Halo! Kode OTP Reset PIN Keamanan M-PAD Anda adalah: {$otp}. Berlaku selama 5 menit.", function ($m) use ($email) {
                    $m->to($email)->subject('[M-PAD Baubau] Kode OTP Reset PIN Keamanan');
                });
                $sentVia[] = 'Email ('.substr($email, 0, 3).'***@'.explode('@', $email)[1].')';
            } catch (\Throwable $e) {
                // Ignore mail sending failure if WA worked
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP berhasil dikirim ke '.implode(' & ', $sentVia),
        ]);
    }

    /**
     * Verify OTP and reset PIN.
     */
    public function resetWithOtp(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun warga/wajib pajak.'], 403);
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'pin_confirmation' => ['required', 'same:pin'],
        ], [
            'pin.regex' => 'PIN harus berupa 6 angka numerik.',
            'pin_confirmation.same' => 'Konfirmasi PIN tidak cocok.',
        ]);

        $cachedOtp = Cache::get("pin_reset_otp_{$user->id}");
        if (! $cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa.',
                'errors' => ['otp' => ['Kode OTP salah atau telah kedaluwarsa.']],
            ], 422);
        }

        if (in_array($request->pin, $this->weakPins, true)) {
            return response()->json([
                'message' => 'PIN terlalu mudah ditebak. Hindari kombinasi angka berulang atau berurutan.',
                'errors' => ['pin' => ['PIN terlalu mudah ditebak. Gunakan kombinasi angka lain.']],
            ], 422);
        }

        Cache::forget("pin_reset_otp_{$user->id}");

        $user->pin_hash = Hash::make($request->pin);
        $user->pin_set_at = now();
        $user->pin_failed_attempts = 0;
        $user->pin_locked_until = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'PIN keamanan berhasil diatur ulang.',
            'has_pin' => true,
        ]);
    }
}

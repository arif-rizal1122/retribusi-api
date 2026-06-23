<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Cache;
use App\Services\WaGatewayService;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->orWhere('nik', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email/NIK atau password salah'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Akun Anda tidak aktif'
            ], 403);
        }

        // Check if OPD user and if their OPD is approved
        if ($user->role === 'opd' && $user->opd) {
            if ($user->opd->status !== 'approved') {
                return response()->json([
                    'message' => 'OPD Anda belum disetujui oleh admin'
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('opd'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('opd')
        ]);
    }

    /**
     * Update authenticated user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . ($user instanceof \App\Models\User ? 'users' : 'taxpayers') . ',email,' . $user->id,
            'surat_penugasan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user->update($request->only('name', 'email'));

        // Handle Surat Penugasan upload
        if ($request->hasFile('surat_penugasan')) {
            $cloudinary = app(\App\Services\CloudinaryService::class);
            $url = $cloudinary->upload(
                $request->file('surat_penugasan'),
                'retribusi/surat_penugasan'
            );

            $metadata = $user->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: [];
            }
            $metadata['surat_penugasan_url'] = $url;
            $user->metadata = $metadata;
            $user->save();
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user->fresh()->load('opd')
        ]);
    }

    /**
     * Change authenticated user password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Update user real-time location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();
        if (!($user instanceof \App\Models\User)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Lokasi diperbarui',
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ]);
    }

    /**
     * Request OTP for citizen login
     */
    public function requestCitizenOtp(Request $request, WaGatewayService $waGateway)
    {
        $request->validate([
            'nik' => 'required|string|size:16',
            'phone' => 'required|string',
        ]);

        $taxpayer = \App\Models\Taxpayer::where('nik', $request->nik)->first();

        if (!$taxpayer) {
            // Auto register taxpayer if not found? No, they should register, but for now we just return error
            return response()->json([
                'message' => 'NIK tidak terdaftar'
            ], 404);
        }

        if (!$taxpayer->is_active) {
            return response()->json([
                'message' => 'Akun Wajib Pajak tidak aktif'
            ], 403);
        }

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);
        
        // Save OTP to Cache for 5 minutes
        Cache::put("otp_citizen_{$request->nik}", $otp, now()->addMinutes(5));

        // Update phone if different
        if ($taxpayer->phone !== $request->phone) {
            $taxpayer->update(['phone' => $request->phone]);
        }

        // Send via WA
        $sent = $waGateway->sendOtp($request->phone, $otp);

        if (!$sent) {
            return response()->json([
                'message' => 'Gagal mengirim OTP via WhatsApp. Pastikan nomor aktif.'
            ], 500);
        }

        return response()->json([
            'message' => 'OTP telah dikirim ke WhatsApp Anda'
        ]);
    }

    /**
     * Verify OTP and Login citizen
     */
    public function verifyCitizenOtp(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16',
            'otp' => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get("otp_citizen_{$request->nik}");

        // Bypass for demo account logic (1234567890123456)
        $isDemo = ($request->nik === '1234567890123456' || $request->nik === '1234567890123457') && $request->otp === '123456';

        if (!$isDemo && (!$cachedOtp || $cachedOtp !== $request->otp)) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa'
            ], 401);
        }

        $taxpayer = \App\Models\Taxpayer::where('nik', $request->nik)->first();

        if (!$taxpayer) {
            return response()->json([
                'message' => 'NIK tidak terdaftar'
            ], 404);
        }

        // Clear OTP after successful login
        Cache::forget("otp_citizen_{$request->nik}");

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        return response()->json([
            'user' => $taxpayer,
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Login berhasil',
        ]);
    }

    /**
     * Login citizen (taxpayer) using NIK and Password (DEPRECATED - keep for backward compat)
     */
    public function citizenLogin(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $taxpayer = \App\Models\Taxpayer::where('nik', $request->nik)->first();

        if (!$taxpayer || !Hash::check($request->password, $taxpayer->password)) {
            return response()->json([
                'message' => 'NIK atau password salah'
            ], 401);
        }

        if (!$taxpayer->is_active) {
            return response()->json([
                'message' => 'Akun Wajib Pajak tidak aktif'
            ], 403);
        }

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        return response()->json([
            'user' => $taxpayer,
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Login berhasil (Citizen Mode)',
        ]);
    }

    /**
     * Register a new citizen (taxpayer)
     */
    public function registerCitizen(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16|unique:taxpayers,nik',
            'name' => 'required|string',
            'opd_id' => 'nullable|exists:opds,id',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $taxpayer = \App\Models\Taxpayer::create([
            'nik' => strip_tags($request->nik),
            'name' => strip_tags($request->name),
            'opd_id' => $request->opd_id, // Will be null if not provided
            'address' => strip_tags($request->address),
            'phone' => strip_tags($request->phone),
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        return response()->json([
            'user' => $taxpayer,
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Registrasi berhasil',
        ], 201);
    }

    /**
     * Change citizen password
     */
    public function changeCitizenPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $taxpayer = $request->user();

        if (!Hash::check($request->current_password, $taxpayer->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai'
            ], 422);
        }

        $taxpayer->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Register a new Notaris/PPAT (Requires Ka.Bapenda approval)
     */
    public function registerNotaris(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16|unique:users,nik',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string',
            'address' => 'required|string',
            'sk_dokumen' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $cloudinary = app(\App\Services\CloudinaryService::class);
        $skUrl = $cloudinary->upload(
            $request->file('sk_dokumen'),
            'retribusi/notaris_sk'
        );

        $user = \App\Models\User::create([
            'name' => strip_tags($request->name),
            'email' => strip_tags($request->email),
            'nik' => strip_tags($request->nik),
            'password' => Hash::make($request->password),
            'phone' => strip_tags($request->phone),
            'address' => strip_tags($request->address),
            'role' => \App\Models\User::ROLE_NOTARIS,
            'status' => 'pending', // Requires approval
            'metadata' => [
                'sk_dokumen_url' => $skUrl,
                'registered_at' => now()->toIso8601String()
            ]
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil. Akun Anda berstatus PENDING dan sedang menunggu proses verifikasi serta aktivasi oleh Kepala Bapenda.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role
            ]
        ], 201);
    }
}

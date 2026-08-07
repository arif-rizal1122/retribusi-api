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
use App\Services\DukcapilNikService;
use Illuminate\Support\Facades\Log;

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
            'phone' => 'required|string',
        ]);

        // Clean phone number (remove +62, leading 0, etc to standardize? For now we just exact match)
        $taxpayer = \App\Models\Taxpayer::where('phone', $request->phone)->first();

        if (!$taxpayer) {
            return response()->json([
                'message' => 'Nomor WhatsApp tidak terdaftar di sistem'
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
        Cache::put("otp_citizen_{$request->phone}", $otp, now()->addMinutes(5));

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
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get("otp_citizen_{$request->phone}");

        // Bypass for demo account logic (1234567890123456)
        $isDemo = ($request->phone === '081234567890') && $request->otp === '123456';

        if (!$isDemo && (!$cachedOtp || $cachedOtp !== $request->otp)) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa'
            ], 401);
        }

        $taxpayer = \App\Models\Taxpayer::where('phone', $request->phone)->first();

        if (!$taxpayer) {
            return response()->json([
                'message' => 'Nomor WhatsApp tidak terdaftar'
            ], 404);
        }

        // Clear OTP after successful login
        Cache::forget("otp_citizen_{$request->phone}");

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        return response()->json([
            'user' => $taxpayer,
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Login berhasil',
        ]);
    }

    public function citizenLogin(Request $request)
    {
        $request->validate([
            'method' => 'required|in:email,nik',
            'identifier' => 'required|string',
            'password' => 'required_if:method,email|string',
        ]);

        $query = \App\Models\Taxpayer::query();
        
        if ($request->method === 'email') {
            $query->where('email', $request->identifier);
        } else {
            $query->where('nik', $request->identifier);
        }
        
        $taxpayer = $query->first();

        if (!$taxpayer) {
            return response()->json([
                'message' => 'Akun tidak ditemukan'
            ], 404);
        }

        if ($request->method === 'email') {
            if (!$taxpayer->password || !Hash::check($request->password, $taxpayer->password)) {
                return response()->json([
                    'message' => 'Email atau password salah'
                ], 401);
            }
        } else {
            // For NIK (IKD Dummy), require mother_name check
            if (!$request->has('mother_name')) {
                return response()->json([
                    'message' => 'Nama Ibu Kandung wajib diisi untuk verifikasi NIK'
                ], 401);
            }

            $metadata = $taxpayer->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: [];
            }
            $storedMotherName = $metadata['mother_name'] ?? null;
            $cleanInput = strtolower(preg_replace('/\s+/', '', $request->mother_name));
            $cleanStored = $storedMotherName ? strtolower(preg_replace('/\s+/', '', $storedMotherName)) : '';
            
            if (!$storedMotherName || $cleanInput !== $cleanStored) {
                return response()->json([
                    'message' => 'Nama Ibu Kandung tidak cocok dengan data verifikasi'
                ], 401);
            }
        }

        if (!$taxpayer->is_active) {
            return response()->json([
                'message' => 'Akun Wajib Pajak tidak aktif'
            ], 403);
        }

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        // Dispatch background job to sync BPN assets
        \App\Jobs\SyncBpnTaxpayerAssetsJob::dispatch($taxpayer);

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
            'email' => 'nullable|email|unique:taxpayers,email',
            'opd_id' => 'nullable|exists:opds,id',
            'password' => 'nullable|string|min:6|confirmed',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'mother_name' => 'nullable|string'
        ]);

        $metadata = [];
        if ($request->has('mother_name')) {
            $metadata['mother_name'] = $request->mother_name;
        }

        // Validasi format NIK (16 digit) + H2H Dukcapil (default not_configured → tidak memblokir)
        $dukcapilService = app(DukcapilNikService::class);
        $nikVerifiedSource = 'none';

        if (! $dukcapilService->validateFormat($request->nik)) {
            return response()->json([
                'message' => 'Format NIK tidak valid.',
                'errors' => ['nik' => ['Format NIK tidak valid.']],
            ], 422);
        }

        if ($dukcapilService->available()) {
            $dukcapil = $dukcapilService->validate($request->nik, $request->name);
            if (($dukcapil['valid'] ?? false) !== true) {
                return response()->json([
                    'message' => 'NIK tidak valid menurut Dukcapil.',
                    'errors' => ['nik' => [$dukcapil['reason'] ?? 'NIK tidak valid menurut Dukcapil.']],
                ], 422);
            }
            $nikVerifiedSource = 'dukcapil';
        } else {
            $nikVerifiedSource = 'local';
            Log::info('DUKCAPIL not configured, NIK validation skipped', [
                'nik' => substr($request->nik, 0, 6).'****',
            ]);
            \App\Models\H2hConnectorLog::record('dukcapil', 'not_configured', null, 'DUKCAPIL_BASE_URL kosong — validasi NIK lokal hanya format', ['nik_prefix' => substr($request->nik, 0, 6).'****']);
        }

        $taxpayer = \App\Models\Taxpayer::create([
            'nik' => strip_tags($request->nik),
            'name' => strip_tags($request->name),
            'email' => $request->email,
            'opd_id' => $request->opd_id, // Will be null if not provided
            'address' => strip_tags($request->address),
            'phone' => strip_tags($request->phone),
            'district' => strip_tags($request->district),
            'sub_district' => strip_tags($request->sub_district),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'password' => $request->password ? Hash::make($request->password) : null,
            'metadata' => $metadata,
            'is_active' => true,
            'nik_verified_source' => $nikVerifiedSource,
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

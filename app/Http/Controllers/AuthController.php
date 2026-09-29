<?php

namespace App\Http\Controllers;

use App\Jobs\SyncBpnTaxpayerAssetsJob;
use App\Models\H2hConnectorLog;
use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\DukcapilNikService;
use App\Services\WaGatewayService;
use App\Support\OpdAccess;
use App\Support\RoleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

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

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email/NIK atau password salah',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Akun Anda tidak aktif',
            ], 403);
        }

        // Check if OPD user and if their OPD is approved
        if ($user->role === 'opd' && $user->opd) {
            if ($user->opd->status !== 'approved') {
                return response()->json([
                    'message' => 'OPD Anda belum disetujui oleh admin',
                ], 403);
            }
        }

        // Isi metadata role (role_code/opd_role/role_group) untuk deteksi role non-warga
        // (FE resolveDashboardRole membaca user.metadata.role_code / opd_role / role).
        RoleResolver::persistToMetadata($user);
        $user->refresh();

        $token = $user->createToken('auth_token')->plainTextToken;

        RoleResolver::enrich($user);

        return response()->json([
            'user' => $user->load('opd'),
            'token' => $token,
            'token_type' => 'Bearer',
            'access' => OpdAccess::for($user),
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        RoleResolver::enrich($user);

        return response()->json([
            'user' => $user->load('opd'),
            'access' => $user instanceof User ? OpdAccess::for($user) : null,
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
            'email' => 'required|string|email|max:255|unique:'.($user instanceof User ? 'users' : 'taxpayers').',email,'.$user->id,
            'surat_penugasan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user->update($request->only('name', 'email'));

        // Handle Surat Penugasan upload
        if ($request->hasFile('surat_penugasan')) {
            $cloudinary = app(CloudinaryService::class);
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
            'user' => $user->fresh()->load('opd'),
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

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah',
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
        if (! ($user instanceof User)) {
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

        // Nomor WA dipakai sebagai kunci login, jadi harus menunjuk SATU akun.
        // Sebelumnya ->first() dipakai begitu saja: kalau nomor dipakai beberapa
        // akun (data warisan impor), warga selalu masuk sebagai akun pertama —
        // bukan akun miliknya, sehingga tagihannya tidak muncul. Sekarang nomor
        // ambigu ditolak dengan pesan yang mengarahkan.
        $candidates = Taxpayer::where('phone', $request->phone)->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'message' => 'Nomor WhatsApp tidak terdaftar di sistem',
            ], 404);
        }

        if ($candidates->count() > 1) {
            // Kalau tepat SATU di antaranya yang jelas milik warga (punya email/NIK
            // lengkap + ada aktivitas tagihan), pakai itu. Sisanya data warisan.
            $withEmail = $candidates->filter(fn ($t) => ! empty($t->email));
            if ($withEmail->count() === 1) {
                $taxpayer = $withEmail->first();
            } else {
                return response()->json([
                    'message' => 'Nomor WhatsApp ini terdaftar di lebih dari satu akun. '
                        .'Silakan hubungi petugas Bapenda untuk menggabungkan data Anda '
                        .'agar tagihan dapat ditampilkan dengan benar.',
                ], 409);
            }
        } else {
            $taxpayer = $candidates->first();
        }

        if (! $taxpayer->is_active) {
            return response()->json([
                'message' => 'Akun Wajib Pajak tidak aktif',
            ], 403);
        }

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Save OTP to Cache for 5 minutes
        Cache::put("otp_citizen_{$request->phone}", $otp, now()->addMinutes(5));

        // Send via WA
        $sent = $waGateway->sendOtp($request->phone, $otp);

        if (! $sent) {
            return response()->json([
                'message' => 'Gagal mengirim OTP via WhatsApp. Pastikan nomor aktif.',
            ], 500);
        }

        return response()->json([
            'message' => 'OTP telah dikirim ke WhatsApp Anda',
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

        if (! $isDemo && (! $cachedOtp || $cachedOtp !== $request->otp)) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa',
            ], 401);
        }

        // Kunci yang sama seperti requestCitizenOtp: nomor ambigu tidak boleh
        // dipilih sembarangan dengan ->first(), harus persis satu akun.
        $candidates = Taxpayer::where('phone', $request->phone)->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'message' => 'Nomor WhatsApp tidak terdaftar',
            ], 404);
        }

        if ($candidates->count() > 1) {
            $withEmail = $candidates->filter(fn ($t) => ! empty($t->email));
            if ($withEmail->count() === 1) {
                $taxpayer = $withEmail->first();
            } else {
                return response()->json([
                    'message' => 'Nomor WhatsApp ini terdaftar di lebih dari satu akun. '
                        .'Silakan hubungi petugas Bapenda untuk menggabungkan data Anda '
                        .'agar tagihan dapat ditampilkan dengan benar.',
                ], 409);
            }
        } else {
            $taxpayer = $candidates->first();
        }

        // Clear OTP after successful login
        Cache::forget("otp_citizen_{$request->phone}");

        RoleResolver::persistToMetadata($taxpayer);
        $taxpayer->refresh();
        RoleResolver::enrich($taxpayer);

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

        $query = Taxpayer::query();

        if ($request->method === 'email') {
            $query->where('email', $request->identifier);
        } else {
            $query->where('nik', $request->identifier);
        }

        $taxpayer = $query->first();

        if (! $taxpayer) {
            return response()->json([
                'message' => 'Akun tidak ditemukan',
            ], 404);
        }

        if ($request->method === 'email') {
            if (! $taxpayer->password || ! Hash::check($request->password, $taxpayer->password)) {
                return response()->json([
                    'message' => 'Email atau password salah',
                ], 401);
            }
        } else {
            // For NIK (IKD Dummy), require mother_name check
            if (! $request->has('mother_name')) {
                return response()->json([
                    'message' => 'Nama Ibu Kandung wajib diisi untuk verifikasi NIK',
                ], 401);
            }

            $metadata = $taxpayer->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: [];
            }
            $storedMotherName = $metadata['mother_name'] ?? null;
            $cleanInput = strtolower(preg_replace('/\s+/', '', $request->mother_name));
            $cleanStored = $storedMotherName ? strtolower(preg_replace('/\s+/', '', $storedMotherName)) : '';

            if (! $storedMotherName || $cleanInput !== $cleanStored) {
                return response()->json([
                    'message' => 'Nama Ibu Kandung tidak cocok dengan data verifikasi',
                ], 401);
            }
        }

        if (! $taxpayer->is_active) {
            return response()->json([
                'message' => 'Akun Wajib Pajak tidak aktif',
            ], 403);
        }

        RoleResolver::persistToMetadata($taxpayer);
        $taxpayer->refresh();
        RoleResolver::enrich($taxpayer);

        $token = $taxpayer->createToken('citizen_token')->plainTextToken;

        // Dispatch background job to sync BPN assets
        SyncBpnTaxpayerAssetsJob::dispatch($taxpayer);

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
            'nik' => 'nullable|string|size:16|unique:taxpayers,nik',
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
            'mother_name' => 'nullable|string',
        ]);

        $metadata = [];
        if ($request->has('mother_name')) {
            $metadata['mother_name'] = $request->mother_name;
        }

        // Validasi format NIK (16 digit) + H2H Dukcapil (default not_configured → tidak memblokir)
        $dukcapilService = app(DukcapilNikService::class);
        $nikVerifiedSource = 'none';

        if (! empty($request->nik)) {
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
                H2hConnectorLog::record('dukcapil', 'not_configured', null, 'DUKCAPIL_BASE_URL kosong — validasi NIK lokal hanya format', ['nik_prefix' => substr($request->nik, 0, 6).'****']);
            }
        }

        $taxpayer = Taxpayer::create([
            'nik' => ! empty($request->nik) ? strip_tags($request->nik) : null,
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

        RoleResolver::persistToMetadata($taxpayer);
        $taxpayer->refresh();
        RoleResolver::enrich($taxpayer);

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

        if (! Hash::check($request->current_password, $taxpayer->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai',
            ], 422);
        }

        $taxpayer->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah',
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

        $cloudinary = app(CloudinaryService::class);
        $skUrl = $cloudinary->upload(
            $request->file('sk_dokumen'),
            'retribusi/notaris_sk'
        );

        $user = User::create([
            'name' => strip_tags($request->name),
            'email' => strip_tags($request->email),
            'nik' => strip_tags($request->nik),
            'password' => Hash::make($request->password),
            'phone' => strip_tags($request->phone),
            'address' => strip_tags($request->address),
            'role' => User::ROLE_NOTARIS,
            'status' => 'pending', // Requires approval
            'metadata' => [
                'sk_dokumen_url' => $skUrl,
                'registered_at' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil. Akun Anda berstatus PENDING dan sedang menunggu proses verifikasi serta aktivasi oleh Kepala Bapenda.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role,
            ],
        ], 201);
    }

    /**
     * Login/Register citizen via Google Sign-In (ID Token verification).
     *
     * Flow:
     * 1. Verifikasi ID Token secara kriptografis menggunakan Google_Client.
     * 2. Blokir jika email sudah terdaftar sebagai staf dinas (tabel users).
     * 3. Cari/buat Taxpayer berdasarkan google_id atau email.
     * 4. Terbitkan token Sanctum (citizen_token).
     *
     * Keamanan: Google hanya sebagai IdP — NIK, NPWPD, dan data fiskal
     * tetap dikelola oleh M-PAD. Tidak ada data warga yang dikirim ke Google.
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $clientId = config('services.google.client_id');

        if (empty($clientId)) {
            return response()->json([
                'message' => 'Google Sign-In belum dikonfigurasi di server.',
            ], 503);
        }

        $client = new \Google_Client(['client_id' => $clientId]);
        $payload = $client->verifyIdToken($request->id_token);

        if (! $payload) {
            return response()->json([
                'message' => 'Token Google tidak valid atau telah kedaluwarsa.',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'Warga M-PAD';
        $avatar = $payload['picture'] ?? null;

        if (! $email) {
            return response()->json([
                'message' => 'Akun Google tidak menyertakan alamat email.',
            ], 422);
        }

        // Keamanan: Cegah email staf dinas (tabel users) masuk via Google
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'Email ini terdaftar sebagai akun internal Pemkot. '
                    .'Silakan gunakan portal login resmi.',
            ], 403);
        }

        return DB::transaction(function () use ($googleId, $email, $name, $avatar) {
            // Cari taxpayer existing by google_id ATAU email
            $taxpayer = Taxpayer::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if ($taxpayer) {
                // Link Google ID jika belum terpasang
                $updates = [];
                if (! $taxpayer->google_id) {
                    $updates['google_id'] = $googleId;
                }
                if ($avatar && $taxpayer->avatar_url !== $avatar) {
                    $updates['avatar_url'] = $avatar;
                }
                $meta = is_array($taxpayer->metadata) ? $taxpayer->metadata : [];
                if ($avatar && ($meta['avatar_url'] ?? null) !== $avatar) {
                    $meta['avatar_url'] = $avatar;
                    $updates['metadata'] = $meta;
                }
                if (! empty($updates)) {
                    $taxpayer->update($updates);
                }
            } else {
                // Auto-register sebagai Taxpayer baru (Tier 1 — tanpa NIK)
                // NPWPD otomatis di-generate oleh Taxpayer::creating() boot event
                $metadata = [];
                if ($avatar) {
                    $metadata['avatar_url'] = $avatar;
                }
                $taxpayer = Taxpayer::create([
                    'name' => strip_tags($name),
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar_url' => $avatar,
                    'metadata' => $metadata,
                    'is_active' => true,
                    // password null → tidak bisa login via email/password
                    // nik null → Tier 1, belum terverifikasi yuridis
                ]);
            }

            if (! $taxpayer->is_active) {
                return response()->json([
                    'message' => 'Akun Wajib Pajak tidak aktif',
                ], 403);
            }

            RoleResolver::persistToMetadata($taxpayer);
            $taxpayer->refresh();
            RoleResolver::enrich($taxpayer);

            $token = $taxpayer->createToken('citizen_token')->plainTextToken;

            // Background sync BPN assets (existing pattern)
            SyncBpnTaxpayerAssetsJob::dispatch($taxpayer);

            return response()->json([
                'user' => $taxpayer,
                'token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Login berhasil (Google)',
            ]);
        });
    }

    /**
     * Request OTP to link/verify WhatsApp phone number for an authenticated citizen.
     */
    public function requestLinkPhoneOtp(Request $request, WaGatewayService $waGateway)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun wajib pajak/warga.'], 403);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $request->phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        // Cek jika nomor ini sudah dipakai oleh akun taxpayer lain yang aktif
        $duplicate = Taxpayer::where('phone', $cleanPhone)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Nomor WhatsApp ini sudah terdaftar pada akun warga lain.',
                'errors' => ['phone' => ['Nomor WhatsApp ini sudah terdaftar pada akun warga lain.']],
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put("otp_link_phone_{$user->id}_{$cleanPhone}", $otp, now()->addMinutes(5));

        $sent = $waGateway->sendOtp($cleanPhone, $otp);
        if (! $sent) {
            return response()->json([
                'message' => 'Gagal mengirim OTP via WhatsApp. Pastikan nomor aktif.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP berhasil dikirim ke WhatsApp '.$cleanPhone,
        ]);
    }

    /**
     * Verify OTP and link WhatsApp phone number to authenticated citizen.
     */
    public function verifyLinkPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();
        if (! $user instanceof Taxpayer) {
            return response()->json(['message' => 'Hanya untuk akun wajib pajak/warga.'], 403);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $request->phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $cacheKey = "otp_link_phone_{$user->id}_{$cleanPhone}";
        $cachedOtp = Cache::get($cacheKey);

        if (! $cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json([
                'message' => 'Kode OTP salah atau telah kedaluwarsa.',
                'errors' => ['otp' => ['Kode OTP salah atau telah kedaluwarsa.']],
            ], 422);
        }

        Cache::forget($cacheKey);

        $metadata = is_array($user->metadata) ? $user->metadata : [];
        $metadata['phone_verified_at'] = now()->toIso8601String();
        $metadata['phone_verified'] = true;

        $user->phone = $cleanPhone;
        $user->metadata = $metadata;
        $user->save();

        RoleResolver::enrich($user);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil diverifikasi dan ditautkan!',
            'user' => $user,
        ]);
    }
}

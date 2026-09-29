<?php

namespace App\Http\Controllers;

use App\Jobs\SyncBpnTaxpayerAssetsJob;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\DukcapilNikService;
use App\Support\RoleResolver;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isTaxpayer = $user instanceof Taxpayer;

        $load = ['opd'];
        if ($isTaxpayer) {
            $load = array_merge($load, ['retributionTypes', 'retributionClassifications']);
        }

        // Suntik atribut role (role_code/opd_role/role_group) untuk deteksi role non-warga.
        RoleResolver::enrich($user);

        return response()->json([
            'data' => $user->load($load),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $cloudinary = app(CloudinaryService::class);
        $isTaxpayer = $user instanceof Taxpayer;

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nik' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'avatar_url' => 'nullable|string|max:1000',
            'surat_penugasan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'mother_name' => 'nullable|string|max:255',
        ]);

        // Basic fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('address')) {
            $user->address = $request->address;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('district')) {
            $user->district = $request->district;
        }
        if ($request->has('sub_district')) {
            $user->sub_district = $request->sub_district;
        }
        if ($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        if ($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }

        // NIK update & validation for Taxpayers
        if ($isTaxpayer && $request->has('nik')) {
            $rawNik = trim((string) $request->nik);
            if ($rawNik !== '' && $rawNik !== (string) $user->nik) {
                $dukcapilService = app(DukcapilNikService::class);
                if (! $dukcapilService->validateFormat($rawNik)) {
                    return response()->json([
                        'message' => 'Format NIK tidak valid. Pastikan 16 digit sesuai KTP elektronik.',
                        'errors' => ['nik' => ['Format NIK tidak valid. Pastikan 16 digit sesuai KTP elektronik.']],
                    ], 422);
                }

                $nikExists = Taxpayer::where('nik', $rawNik)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($nikExists) {
                    return response()->json([
                        'message' => 'NIK sudah digunakan oleh akun warga lain.',
                        'errors' => ['nik' => ['NIK sudah terdaftar pada akun lain.']],
                    ], 422);
                }

                if ($dukcapilService->available()) {
                    $dukcapil = $dukcapilService->validate($rawNik, $user->name);
                    if (($dukcapil['valid'] ?? false) !== true) {
                        return response()->json([
                            'message' => 'NIK tidak valid menurut Dukcapil.',
                            'errors' => ['nik' => [$dukcapil['reason'] ?? 'NIK tidak valid menurut Dukcapil.']],
                        ], 422);
                    }
                    $user->nik_verified_source = 'dukcapil';
                } else {
                    $user->nik_verified_source = 'local';
                }

                $user->nik = $rawNik;

                try {
                    SyncBpnTaxpayerAssetsJob::dispatch($user);
                } catch (\Throwable $e) {
                    \Log::warning('SyncBpnTaxpayerAssetsJob error on NIK update: '.$e->getMessage());
                }
            }
        }

        // Email update (with unique check only if email changed)
        if ($request->has('email')) {
            $newEmail = ! empty($request->email) ? strtolower(trim((string) $request->email)) : null;
            $currentEmail = ! empty($user->email) ? strtolower(trim((string) $user->email)) : null;

            if ($newEmail !== $currentEmail) {
                if (! empty($newEmail)) {
                    $emailExists = false;
                    if ($isTaxpayer) {
                        $emailExists = Taxpayer::where('email', $newEmail)
                            ->where('id', '!=', $user->id)
                            ->exists();
                    } else {
                        $emailExists = User::where('email', $newEmail)
                            ->where('id', '!=', $user->id)
                            ->exists();
                    }

                    if ($emailExists) {
                        return response()->json([
                            'message' => 'Email sudah digunakan',
                            'errors' => ['email' => ['Email sudah terdaftar. Gunakan email lain.']],
                        ], 422);
                    }
                }
                $user->email = $newEmail;
            }
        }

        // Metadata fields
        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        if ($request->has('mother_name')) {
            $metadata['mother_name'] = $request->mother_name;
        }

        // Handle Avatar URL directly (from decoupled upload)
        if ($request->filled('avatar_url')) {
            $metadata['avatar_url'] = $request->avatar_url;
            if ($isTaxpayer) {
                $user->avatar_url = $request->avatar_url;
            }
        }

        // Handle Avatar/Photo Upload (multipart)
        if ($request->hasFile('avatar')) {
            $folder = $isTaxpayer ? 'avatars/taxpayers' : 'avatars/users';
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'),
                $folder
            );
            $metadata['avatar_url'] = $avatarUrl;
            if ($isTaxpayer) {
                $user->avatar_url = $avatarUrl;
            }
        }

        // Handle Surat Penugasan Upload
        if ($request->hasFile('surat_penugasan')) {
            $url = $cloudinary->upload(
                $request->file('surat_penugasan'),
                'retribusi/surat_penugasan'
            );
            $metadata['surat_penugasan_url'] = $url;
        }

        $user->metadata = $metadata;
        $user->save();

        // Log for debugging
        \Log::info('Profile updated for ID: '.$user->id.' (Type: '.class_basename($user).')', [
            'name' => $user->name,
            'has_avatar' => isset($metadata['avatar_url']),
        ]);

        $load = ['opd'];
        if ($isTaxpayer) {
            $load = array_merge($load, ['retributionTypes', 'retributionClassifications']);
        }

        $freshUser = $user->fresh()->load($load);
        RoleResolver::enrich($freshUser);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $freshUser,
            'user' => $freshUser,
        ]);
    }
}

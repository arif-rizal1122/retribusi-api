<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxpayer;
use App\Services\CloudinaryService;

class MeController extends Controller
{
    /**
     * Get current user profile (Taxpayer/Citizen or User)
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isTaxpayer = $user instanceof \App\Models\Taxpayer;

        $load = ['opd'];
        if ($isTaxpayer) {
            $load = array_merge($load, ['retributionTypes', 'retributionClassifications']);
        }

        return response()->json([
            'data' => $user->load($load)
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $cloudinary = app(CloudinaryService::class);
        $isTaxpayer = $user instanceof \App\Models\Taxpayer;

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'surat_penugasan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Basic fields
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('address')) $user->address = $request->address;
        if ($request->has('phone')) $user->phone = $request->phone;
        
        // Email update (with unique check)
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        // Metadata fields
        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Handle Avatar/Photo Upload
        if ($request->hasFile('avatar')) {
            $folder = $isTaxpayer ? 'avatars/taxpayers' : 'avatars/users';
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'), 
                $folder
            );
            $metadata['avatar_url'] = $avatarUrl;
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
        \Log::info('Profile updated for ID: ' . $user->id . ' (Type: ' . class_basename($user) . ')', [
            'name' => $user->name,
            'has_avatar' => isset($metadata['avatar_url'])
        ]);

        $load = ['opd'];
        if ($isTaxpayer) {
            $load = array_merge($load, ['retributionTypes', 'retributionClassifications']);
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load($load)
        ]);
    }
}

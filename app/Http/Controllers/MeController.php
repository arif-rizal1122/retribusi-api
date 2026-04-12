<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxpayer;
use App\Services\CloudinaryService;

class MeController extends Controller
{
<<<<<<< HEAD
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
=======
    /**
     * Get current user profile (Taxpayer/Citizen)
     */
    public function show(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load(['opd', 'retributionTypes', 'retributionClassifications'])
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
<<<<<<< HEAD
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $cloudinary = app(CloudinaryService::class);
        $isTaxpayer = $user instanceof \App\Models\Taxpayer;
=======
        $cloudinary = app(CloudinaryService::class);
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
<<<<<<< HEAD
            'surat_penugasan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
=======
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
        ]);

        // Basic fields
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('address')) $user->address = $request->address;
        if ($request->has('phone')) $user->phone = $request->phone;
        
<<<<<<< HEAD
        // Email update (with unique check)
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
=======
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
        // Metadata fields
        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Handle Avatar/Photo Upload
        if ($request->hasFile('avatar')) {
<<<<<<< HEAD
            $folder = $isTaxpayer ? 'avatars/taxpayers' : 'avatars/users';
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'), 
                $folder
=======
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'), 
                'avatars/taxpayers'
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
            );
            $metadata['avatar_url'] = $avatarUrl;
        }

<<<<<<< HEAD
        // Handle Surat Penugasan Upload
        if ($request->hasFile('surat_penugasan')) {
            $url = $cloudinary->upload(
                $request->file('surat_penugasan'),
                'retribusi/surat_penugasan'
            );
            $metadata['surat_penugasan_url'] = $url;
        }

=======
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
        $user->metadata = $metadata;
        $user->save();

        // Log for debugging
<<<<<<< HEAD
        \Log::info('Profile updated for ID: ' . $user->id . ' (Type: ' . class_basename($user) . ')', [
=======
        \Log::info('Profile updated for taxpayer ID: ' . $user->id, [
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
            'name' => $user->name,
            'has_avatar' => isset($metadata['avatar_url'])
        ]);

<<<<<<< HEAD
        $load = ['opd'];
        if ($isTaxpayer) {
            $load = array_merge($load, ['retributionTypes', 'retributionClassifications']);
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load($load)
=======
        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load(['opd', 'retributionTypes', 'retributionClassifications'])
>>>>>>> 4fe827787745eafc96dec7695af1fbbe71bfdad4
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxpayer;
use App\Services\CloudinaryService;

class MeController extends Controller
{
    /**
     * Get current user profile (Taxpayer/Citizen)
     */
    public function show(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load(['opd', 'retributionTypes', 'retributionClassifications'])
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $cloudinary = app(CloudinaryService::class);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Basic fields
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('address')) $user->address = $request->address;
        if ($request->has('phone')) $user->phone = $request->phone;
        
        // Metadata fields
        $metadata = $user->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Handle Avatar/Photo Upload
        if ($request->hasFile('avatar')) {
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'), 
                'avatars/taxpayers'
            );
            $metadata['avatar_url'] = $avatarUrl;
        }

        $user->metadata = $metadata;
        $user->save();

        // Log for debugging
        \Log::info('Profile updated for taxpayer ID: ' . $user->id, [
            'name' => $user->name,
            'has_avatar' => isset($metadata['avatar_url'])
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load(['opd', 'retributionTypes', 'retributionClassifications'])
        ]);
    }
}

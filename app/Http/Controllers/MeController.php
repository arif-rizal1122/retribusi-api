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

    /**
     * Update current user profile
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $cloudinary = app(CloudinaryService::class);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048', // Allow avatar upload
        ]);

        $data = $request->only(['name', 'address', 'phone']);
        
        // Handle Avatar/Photo Upload
        if ($request->hasFile('avatar')) {
            $avatarUrl = $cloudinary->upload(
                $request->file('avatar'), 
                'avatars/taxpayers'
            );
            
            $metadata = $user->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: [];
            }
            $metadata['avatar_url'] = $avatarUrl;
            $data['metadata'] = $metadata;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load(['opd', 'retributionTypes', 'retributionClassifications'])
        ]);
    }
}

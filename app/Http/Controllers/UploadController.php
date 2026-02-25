<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Handle general image upload to Cloudinary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|mimes:jpeg,png,jpg,gif,heic|max:5120', // 5MB max
            'folder' => 'nullable|string|max:100',
        ]);

        try {
            $folder = $request->input('folder', 'retribusi/general');
            
            if ($request->hasFile('image')) {
                // Upload file to Cloudinary via Laravel Storage
                $path = $request->file('image')->store($folder, 'cloudinary');
                $url = Storage::disk('cloudinary')->url($path);

                return response()->json([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'url' => $url,
                    'path' => $path
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file found in request'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

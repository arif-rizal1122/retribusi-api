<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary, or local public storage when Cloudinary is not configured.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string Cloudinary URL
     */
    public function upload(UploadedFile $file, string $folder = 'retribusi'): string
    {
        if (!$this->hasCloudinaryConfig()) {
            return $this->uploadLocal($file, $folder);
        }

        try {
            /** @var \Cloudinary\Cloudinary $cloudinary */
            $cloudinary = app(\Cloudinary\Cloudinary::class);
            
            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'auto', // auto-detect file type (image, video, raw)
            ]);
            
            return $result['secure_url'];
        } catch (\Exception $e) {
            \Log::error('Cloudinary upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            if (app()->environment(['local', 'development', 'testing'])) {
                return $this->uploadLocal($file, $folder);
            }

            throw new \Exception('Gagal upload file: ' . $e->getMessage());
        }
    }

    private function hasCloudinaryConfig(): bool
    {
        $disk = config('filesystems.disks.cloudinary', []);

        return !empty($disk['url']) || (
            !empty($disk['cloud']) &&
            !empty($disk['key']) &&
            !empty($disk['secret'])
        );
    }

    private function uploadLocal(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/\\') ?: 'retribusi';
        $path = $file->store($folder, 'public');

        return url('/storage/' . ltrim($path, '/'));
    }

    /**
     * Delete a file by URL from Cloudinary or local public storage.
     *
     * @param string|null $url
     * @return bool
     */
    public function delete(?string $url): bool
    {
        if (!$url) return false;

        if (!str_contains($url, 'cloudinary')) {
            $path = parse_url($url, PHP_URL_PATH);
            $path = $path ? preg_replace('#^/storage/#', '', $path) : null;

            return $path ? Storage::disk('public')->delete($path) : false;
        }

        try {
            /** @var \Cloudinary\Cloudinary $cloudinary */
            $cloudinary = app(\Cloudinary\Cloudinary::class);

            // Extract public ID from URL
            $path = parse_url($url, PHP_URL_PATH);
            $segments = explode('/', $path);
            
            // Public ID is everything after 'upload/v...'
            $foundUpload = false;
            $publicIdSegments = [];
            foreach ($segments as $segment) {
                if ($foundUpload) {
                    $publicIdSegments[] = $segment;
                }
                if (strpos($segment, 'upload') === 0) {
                    $foundUpload = true;
                    // skip version segment if it follows
                }
            }

            // Remove version segment (v1234567) if present
            if (!empty($publicIdSegments) && str_starts_with($publicIdSegments[0], 'v') && is_numeric(substr($publicIdSegments[0], 1))) {
                array_shift($publicIdSegments);
            }

            $fullPublicId = implode('/', $publicIdSegments);
            $fullPublicId = pathinfo($fullPublicId, PATHINFO_DIRNAME) . '/' . pathinfo($fullPublicId, PATHINFO_FILENAME);
            $fullPublicId = ltrim($fullPublicId, './');

            $cloudinary->adminApi()->deleteAssets([$fullPublicId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

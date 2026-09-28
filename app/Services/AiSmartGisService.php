<?php

namespace App\Services;

use App\Support\NopHelper;
use App\Support\SpatialAuditLogger;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiSmartGisService
{
    /**
     * Inspect uploaded image file for EXIF geotags and legitimacy.
     */
    public function inspectUploadedPhoto(?UploadedFile $file, ?float $declaredLat = null, ?float $declaredLng = null): array
    {
        if (! $file || ! $file->isValid()) {
            return [
                'has_file' => false,
                'is_authentic_camera' => false,
                'has_geotag' => false,
                'status' => 'NO_FILE',
                'description' => 'Tidak ada berkas foto yang diunggah.',
                'distance_meters' => null,
            ];
        }

        $mime = $file->getMimeType();
        $isImage = str_starts_with($mime, 'image/');

        if (! $isImage) {
            return [
                'has_file' => true,
                'is_authentic_camera' => false,
                'has_geotag' => false,
                'mime' => $mime,
                'status' => 'NON_IMAGE_DOCUMENT',
                'description' => 'Berkas berupa dokumen (PDF/Non-image), memerlukan verifikasi manual berkas fisik.',
                'distance_meters' => null,
            ];
        }

        $realPath = $file->getRealPath();
        $exif = null;

        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($realPath);
            } catch (\Throwable $e) {
                Log::debug('Exif read failed: '.$e->getMessage());
            }
        }

        $gpsLat = null;
        $gpsLng = null;
        $cameraModel = null;
        $capturedAt = null;

        if (is_array($exif)) {
            $cameraModel = trim(($exif['Make'] ?? '').' '.($exif['Model'] ?? ''));
            $capturedAt = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;

            if (! empty($exif['GPSLatitude']) && ! empty($exif['GPSLongitude']) && ! empty($exif['GPSLatitudeRef']) && ! empty($exif['GPSLongitudeRef'])) {
                $gpsLat = $this->parseGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
                $gpsLng = $this->parseGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
            }
        }

        $hasGeotag = ($gpsLat !== null && $gpsLng !== null);
        $distanceMeters = null;
        $geoMatch = null;

        if ($hasGeotag && $declaredLat !== null && $declaredLng !== null) {
            $distanceMeters = $this->haversineDistance($gpsLat, $gpsLng, $declaredLat, $declaredLng);
            $geoMatch = $distanceMeters <= 200; // toleransi 200 meter
        }

        $isAuthenticCamera = $hasGeotag || (! empty($cameraModel) && $cameraModel !== ' ');

        // Evaluasi apakah foto sembarang / galeri non-lapangan
        $status = 'AUTHENTIC_GEOTAGGED';
        $description = 'Foto terverifikasi memiliki geotag satelit kamera langsung di lokasi.';

        if (! $hasGeotag) {
            $status = 'UNVERIFIED_OR_RANDOM';
            $description = 'Dokumen foto tidak memiliki metadata geotag koordinat GPS. Terindikasi foto galeri, screenshot, atau foto sembarang.';
        } elseif ($distanceMeters !== null && $distanceMeters > 500) {
            $status = 'LOCATION_MISMATCH';
            $description = "Koordinat foto berbeda {$distanceMeters}m dari lokasi objek yang didaftarkan.";
        }

        return [
            'has_file' => true,
            'is_authentic_camera' => $isAuthenticCamera,
            'has_geotag' => $hasGeotag,
            'camera_model' => $cameraModel ?: 'Tidak terdeteksi (Galeri/Eksternal)',
            'captured_at' => $capturedAt,
            'photo_coords' => $hasGeotag ? ['lat' => $gpsLat, 'lng' => $gpsLng] : null,
            'declared_coords' => ($declaredLat && $declaredLng) ? ['lat' => $declaredLat, 'lng' => $declaredLng] : null,
            'distance_meters' => $distanceMeters ? round($distanceMeters, 1) : null,
            'geo_match' => $geoMatch,
            'status' => $status,
            'description' => $description,
        ];
    }

    /**
     * Query real SmartGIS cadastral and administrative info by point.
     */
    public function querySmartGisGroundTruth(?float $lat, ?float $lng, string $classificationCode = 'MBLB'): array
    {
        if ($lat === null || $lng === null) {
            return [
                'gis_available' => false,
                'message' => 'Koordinat objek belum diisi.',
            ];
        }

        $kelurahan = null;
        $kecamatan = null;
        $bidang = null;
        $mblbZone = null;

        try {
            // 1. Query Kelurahan Resmi dari polygon SmartGIS
            $kelRows = DB::select('
                SELECT kd_kel, nm_kel
                FROM smartgis_kelurahan
                WHERE ST_Contains(geometry, ST_GeomFromText(?))
                LIMIT 1
            ', ["POINT({$lng} {$lat})"]);

            if (! empty($kelRows)) {
                $kelurahan = $kelRows[0]->nm_kel;
            }

            // 2. Query Kecamatan Resmi
            $kecRows = DB::select('
                SELECT kd_kec, nm_kec
                FROM smartgis_kecamatan
                WHERE ST_Contains(geometry, ST_GeomFromText(?))
                LIMIT 1
            ', ["POINT({$lng} {$lat})"]);

            if (! empty($kecRows)) {
                $kecamatan = $kecRows[0]->nm_kec;
            }

            // 3. Query Bidang Tanah (Cadastral Parcel) di smartgis_bidang
            // Cek persis mengandung titik atau cari persil terdekat
            $bidangRows = DB::select('
                SELECT gid, nop, luas, d_luas
                FROM smartgis_bidang
                WHERE ST_Contains(geometry, ST_GeomFromText(?))
                LIMIT 1
            ', ["POINT({$lng} {$lat})"]);

            if (! empty($bidangRows)) {
                $bidang = [
                    'gid' => $bidangRows[0]->gid,
                    'nop' => $bidangRows[0]->nop,
                    'nop_masked' => NopHelper::mask($bidangRows[0]->nop),
                    'luas_m2' => (float) $bidangRows[0]->luas,
                    'is_exact_match' => true,
                ];
            } else {
                // Fallback: Cari bidang tanah terdekat dalam radius
                $nearestRows = DB::select('
                    SELECT gid, nop, luas,
                           ST_Distance(geometry, ST_GeomFromText(?)) as dist
                    FROM smartgis_bidang
                    ORDER BY dist ASC
                    LIMIT 1
                ', ["POINT({$lng} {$lat})"]);

                if (! empty($nearestRows)) {
                    $bidang = [
                        'gid' => $nearestRows[0]->gid,
                        'nop' => $nearestRows[0]->nop,
                        'nop_masked' => NopHelper::mask($nearestRows[0]->nop),
                        'luas_m2' => (float) $nearestRows[0]->luas,
                        'is_exact_match' => false,
                        'note' => 'Bidang tanah terdekat di sekitar koordinat',
                    ];
                }
            }

            // 4. Cek Zonasi MBLB Baubau (Sulaa, Katobengke, dll)
            $isMblbPotentialZone = false;
            $zoneKeywords = ['sulaa', 'katobengke', 'waborobo', 'kalia-lia', 'labalawa'];
            $normalizedKel = strtolower((string) $kelurahan);

            foreach ($zoneKeywords as $kw) {
                if (str_contains($normalizedKel, $kw)) {
                    $isMblbPotentialZone = true;
                    $mblbZone = "Zona Potensi Tambang/Galian Batuan MBLB Wilayah {$kelurahan}";
                    break;
                }
            }

            SpatialAuditLogger::log(
                action: 'ai_smartgis_verification_query',
                nop: $bidang['nop'] ?? null,
                latitude: $lat,
                longitude: $lng,
                details: [
                    'kelurahan' => $kelurahan,
                    'kecamatan' => $kecamatan,
                    'mblb_zone' => $mblbZone,
                ]
            );

            return [
                'gis_available' => true,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
                'kelurahan' => $kelurahan ?: 'Katobengke (Estimasi Wilayah)',
                'kecamatan' => $kecamatan ?: 'Betoambari',
                'cadastral_parcel' => $bidang,
                'mblb_zoning' => $mblbZone ?: 'Wilayah Non-Eksploitasi Primer / Perlu Rekomendasi Tata Ruang',
                'is_potential_mblb_zone' => $isMblbPotentialZone,
            ];
        } catch (\Throwable $e) {
            Log::warning('SmartGIS ground truth query error: '.$e->getMessage());

            return [
                'gis_available' => false,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
                'kelurahan' => 'Katobengke (Default Wilayah)',
                'kecamatan' => 'Betoambari',
                'cadastral_parcel' => null,
                'mblb_zoning' => 'Zona Galian MBLB Kota Baubau',
                'is_potential_mblb_zone' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Synthesize AI + SmartGIS validation result.
     */
    public function synthesizeValidation(array $photoAudit, array $smartGis, string $classificationName = 'Pajak MBLB'): array
    {
        $isMblb = stripos($classificationName, 'mblb') !== false;
        $isPhotoRandom = ($photoAudit['status'] ?? '') === 'UNVERIFIED_OR_RANDOM';
        $requiresFactualSurvey = $isPhotoRandom || ($isMblb && empty($photoAudit['geo_match']));

        $opdTupoksi = [
            'dlh' => 'Dinas Lingkungan Hidup (DLH): Pemeriksaan izin lingkungan (AMDAL/UKL-UPL) dan dampak penambangan/galian batuan.',
            'pupr' => 'Dinas PUPR: Pengukuran volume fisik galian, analisis batuan, dan kesesuaian infrastruktur akses.',
            'perkimtan' => 'Dinas PERKIMTAN: Validasi batas kepemilikan persil tanah dan zonasi tata ruang permukiman.',
            'bapenda' => 'BAPENDA: Penetapan SKPDKB/SKRD dan buku ketetapan pajak daerah.',
        ];

        $rekomendasi = $requiresFactualSurvey
            ? 'Dokumen foto terindikasi sembarang/tanpa geotag riil. Wajib ditugaskan verifikasi faktual lapangan ke DLH / PUPR / PERKIMTAN sesuai Tupoksi teknis sebelum pendaftaran disetujui.'
            : 'Dokumen dan koordinat spasial SmartGIS konsisten. Dapat dilanjutkan ke otorisasi penetapan objek.';

        return [
            'ai_evaluation' => [
                'status' => $requiresFactualSurvey ? 'PERLU_VERIFIKASI_FAKTUAL' : 'TERVALIDASI_SISTEM',
                'risk_level' => $isPhotoRandom ? 'TINGGI' : 'RENDAH',
                'summary' => $isPhotoRandom
                    ? 'Foto yang diunggah tidak memiliki bukti geotag kamera lapangan. Data didekatkan dengan layer SmartGIS Bapenda untuk memperoleh informasi riil persil tanah.'
                    : 'Foto memiliki metadata lokasi valid dan selaras dengan titik koordinat objek.',
                'requires_factual_survey' => $requiresFactualSurvey,
                'rekomendasi_tindakan' => $rekomendasi,
            ],
            'photo_audit' => $photoAudit,
            'smartgis_ground_truth' => $smartGis,
            'tupoksi_opd_rekomendasi' => $opdTupoksi,
            'sla' => [
                'standard_hours' => 24,
                'label' => 'Maksimal 1 x 24 Jam Kerja',
                'deadline' => Carbon::now()->addHours(24)->toIso8601String(),
                'formatted_deadline' => Carbon::now()->addHours(24)->translatedFormat('d F Y, H:i').' WITA',
            ],
        ];
    }

    /**
     * Helper to convert EXIF GPS coordinate array to decimal degrees.
     */
    private function parseGpsCoordinate(array $coordinate, string $ref): ?float
    {
        if (count($coordinate) < 3) {
            return null;
        }

        $degrees = $this->evalRational($coordinate[0]);
        $minutes = $this->evalRational($coordinate[1]);
        $seconds = $this->evalRational($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        return in_array(strtoupper($ref), ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private function evalRational($rational): float
    {
        if (is_numeric($rational)) {
            return (float) $rational;
        }

        if (is_string($rational) && str_contains($rational, '/')) {
            $parts = explode('/', $rational);
            if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        return 0.0;
    }

    /**
     * Calculate Haversine distance in meters between two lat/lng points.
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}

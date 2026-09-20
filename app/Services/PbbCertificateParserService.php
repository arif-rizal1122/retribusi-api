<?php

namespace App\Services;

use App\Models\NibNopMapping;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class PbbCertificateParserService
{
    protected PbgDocumentParserService $baseParser;
    protected BhumiService $bhumiService;

    public function __construct(PbgDocumentParserService $baseParser, BhumiService $bhumiService)
    {
        $this->baseParser = $baseParser;
        $this->bhumiService = $bhumiService;
    }

    /**
     * Parse dokumen sertifikat tanah (PDF atau Gambar) dan integrasikan dengan data BPN
     *
     * @param UploadedFile|string $file
     * @return array
     */
    public function parse($file): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $mime = is_string($file) ? mime_content_type($file) : $file->getMimeType();
        $text = '';

        if (str_contains((string) $mime, 'pdf') || (is_string($file) && str_ends_with(strtolower($file), '.pdf'))) {
            $text = $this->baseParser->extractTextFromPdf($filePath);
        } else {
            $text = $this->extractTextFromImage($filePath);
        }

        $attributes = $this->extractAttributesFromText($text);

        // Skema Integrasi BPN (Auto-Lookup ke data ATR/BPN)
        $bpnData = $this->enrichWithBpnData($attributes);

        return array_merge($attributes, [
            'bpn_integration' => $bpnData,
        ]);
    }

    /**
     * Ekstrak teks dari gambar secara mandiri (Image processing / OCR lokal)
     */
    protected function extractTextFromImage(string $filePath): string
    {
        if (function_exists('shell_exec')) {
            $cmd = 'tesseract ' . escapeshellarg($filePath) . ' stdout --oem 1 -l ind+eng 2>/dev/null';
            $output = @shell_exec($cmd);
            if (!empty($output)) {
                return $output;
            }
        }

        return '';
    }

    /**
     * Ekstrak atribut sertifikat tanah dari teks
     */
    public function extractAttributesFromText(string $text): array
    {
        $data = [
            'certificate_type' => 'SHM', // Default Sertipikat Hak Milik
            'certificate_number' => null,
            'nib' => null,
            'land_area' => null,
            'owner_name' => null,
            'district' => null,
            'sub_district' => null,
            'confidence' => 0,
            'raw_text_length' => strlen($text),
        ];

        if (empty(trim($text))) {
            return $data;
        }

        // 1. Tipe Hak (SHM / HGB / Hak Pakai)
        if (preg_match('/(Hak\s*Guna\s*Bangunan|HGB)/i', $text)) {
            $data['certificate_type'] = 'HGB';
        } elseif (preg_match('/(Hak\s*Pakai)/i', $text)) {
            $data['certificate_type'] = 'Hak Pakai';
        } elseif (preg_match('/(Hak\s*Milik|SHM)/i', $text)) {
            $data['certificate_type'] = 'SHM';
        }

        // 2. Nomor Sertifikat / Nomor Hak
        // Contoh: "Hak Milik No. 01234" atau "Nomor : 1234" atau "No. 00451"
        if (preg_match('/(?:Hak\s*(?:Milik|Guna\s*Bangunan|Pakai)\s*(?:No\.?|Nomor)?|Sertipikat\s*No\.?)\s*[:\.]?\s*(\d{3,10})/i', $text, $m)) {
            $data['certificate_number'] = $m[1];
            $data['confidence'] += 25;
        } elseif (preg_match('/(?:No\.?|Nomor)\s*[:\.]?\s*(\d{4,8})\s*(?:\/|\b)/i', $text, $m)) {
            $data['certificate_number'] = $m[1];
            $data['confidence'] += 15;
        }

        // 3. Nomor Identifikasi Bidang (NIB)
        // Format NIB BPN: 13-14 digit, sering dengan format XX.XX.XX.XX.XXXXX atau angka bersambung
        if (preg_match('/(?:NIB|Nomor\s*Identifikasi\s*Bidang)\s*[:\.]?\s*(\d{2}\.\d{2}\.\d{2}\.\d{2}\.\d{4,6}|\d{13,14})/i', $text, $m)) {
            $data['nib'] = str_replace('.', '', $m[1]);
            $data['confidence'] += 30;
        } elseif (preg_match('/\b(2105\d{9,10}|7472\d{9,10})\b/', $text, $m)) {
            // NIB dengan kode wilayah Baubau / Sultra
            $data['nib'] = $m[1];
            $data['confidence'] += 25;
        }

        // 4. Luas Tanah (m2)
        // Contoh: "Luas : 250 M2" atau "Luas Tanah 1.500 m2" atau "seluas 345 m2"
        if (preg_match('/(?:Luas\s*(?:Tanah)?|seluas)\s*[:=]?\s*([0-9\.,]+)\s*(?:m2|M2|m²|Meter\s*Persegi)?/i', $text, $m)) {
            $cleanArea = str_replace('.', '', $m[1]);
            $cleanArea = str_replace(',', '.', $cleanArea);
            $areaFloat = (float) $cleanArea;
            if ($areaFloat > 0 && $areaFloat < 1000000) {
                $data['land_area'] = $areaFloat;
                $data['confidence'] += 25;
            }
        }

        // 5. Kecamatan & Kelurahan di Kota Baubau
        $districts = [
            'Betoambari', 'Murhum', 'Wolio', 'Kokalukuna', 
            'Sorawolio', 'Bungi', 'Lea-Lea', 'Batupoaro'
        ];
        foreach ($districts as $d) {
            if (stripos($text, $d) !== false) {
                $data['district'] = $d;
                $data['confidence'] += 10;
                break;
            }
        }

        $subDistricts = [
            'Wameo', 'Tomba', 'Bataraguru', 'Wale', 'Nganganaumala', 
            'Lamangga', 'Baadia', 'Melai', 'Katobengke', 'Sulaa', 
            'Lipu', 'Waborobo', 'Batu Ampar', 'Liabuku', 'Kalabahi', 
            'Girimulyo', 'Kaisabu Baru', 'Kadia', 'Kadia Baru'
        ];
        foreach ($subDistricts as $sd) {
            if (stripos($text, $sd) !== false) {
                $data['sub_district'] = $sd;
                $data['confidence'] += 10;
                break;
            }
        }

        // 6. Nama Pemegang Hak / Pemilik
        if (preg_match('/(?:Pemegang\s*Hak|Nama\s*Pemilik|Atas\s*Nama)\s*[:\.]?\s*([A-Z\s\.\',]{3,50})(?:\n|\r|NIB|Luas|$)/i', $text, $m)) {
            $candidateName = trim($m[1]);
            if (strlen($candidateName) > 2 && !preg_match('/(NOMOR|SERTIPIKAT|KANTOR|BPN)/i', $candidateName)) {
                $data['owner_name'] = ucwords(strtolower($candidateName));
                $data['confidence'] += 15;
            }
        }

        $data['confidence'] = min(100, $data['confidence']);

        return $data;
    }

    /**
     * Skema Integrasi ATR/BPN BHUMI:
     * Mencocokkan NIB atau Nomor Sertifikat ke data kadaster resmi BPN
     */
    public function enrichWithBpnData(array $attributes): array
    {
        $nib = $attributes['nib'] ?? null;

        $bpnResult = [
            'is_integrated' => false,
            'is_matched' => false,
            'nib' => $nib,
            'nop_pbb' => null,
            'official_land_area' => null,
            'zona_znt' => null,
            'nir_value' => null,
            'taxpayer_name' => null,
            'latitude' => null,
            'longitude' => null,
            'verification_badge' => 'Pendaftaran Mandiri',
            'source' => 'local_draft',
            'notes' => 'Belum ada data pemadanan BPN otomatis.',
        ];

        // 1. Cek di tabel lokal sinkronisasi BPN (NibNopMapping)
        if ($nib) {
            $mapping = NibNopMapping::where('nib', $nib)->first();
            if ($mapping) {
                $bpnResult['is_integrated'] = true;
                $bpnResult['is_matched'] = true;
                $bpnResult['nop_pbb'] = $mapping->nop;
                $bpnResult['official_land_area'] = $mapping->land_area_m2 ? (float) $mapping->land_area_m2 : null;
                $bpnResult['zona_znt'] = $mapping->znt;
                $bpnResult['taxpayer_name'] = $mapping->taxpayer_name;
                $bpnResult['verification_badge'] = '✓ Terverifikasi ATR/BPN BHUMI';
                $bpnResult['source'] = 'nib_nop_mapping';
                $bpnResult['notes'] = 'Data bidang tanah cocok dengan data sinkronisasi BPN.';
                return $bpnResult;
            }
        }

        // 2. Query ke API Service BHUMI BPN jika layanan aktif
        if ($this->bhumiService->available()) {
            if ($nib) {
                $lookup = $this->bhumiService->matchNibNop($nib);
                if (($lookup['matched'] ?? false) === true) {
                    $bpnResult['is_integrated'] = true;
                    $bpnResult['is_matched'] = true;
                    $bpnResult['nop_pbb'] = $lookup['nop'] ?? null;
                    $bpnResult['taxpayer_name'] = $lookup['taxpayer_name'] ?? null;
                    $bpnResult['verification_badge'] = '✓ Terverifikasi ATR/BPN BHUMI';
                    $bpnResult['source'] = 'bhumi_api';
                    $bpnResult['notes'] = 'Data divalidasi langsung via API ATR/BPN BHUMI.';
                    return $bpnResult;
                }
            }
        }

        // 3. Fallback jika ada NIB tapi belum sinkron
        if ($nib) {
            $bpnResult['is_integrated'] = true;
            $bpnResult['verification_badge'] = 'NIB Terdeteksi (Menunggu Pemadanan)';
            $bpnResult['notes'] = "NIB {$nib} terdeteksi dari berkas sertifikat dan akan dicocokkan otomatis oleh sistem Bapenda.";
        }

        return $bpnResult;
    }
}

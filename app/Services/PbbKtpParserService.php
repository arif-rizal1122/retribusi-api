<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class PbbKtpParserService
{
    protected PbgDocumentParserService $baseParser;
    protected DukcapilNikService $dukcapilService;

    public function __construct(PbgDocumentParserService $baseParser, DukcapilNikService $dukcapilService)
    {
        $this->baseParser = $baseParser;
        $this->dukcapilService = $dukcapilService;
    }

    /**
     * Parse data dari berkas e-KTP (PDF atau Gambar)
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

        // Validasi format NIK Dukcapil
        if (!empty($attributes['nik'])) {
            $isValidFormat = $this->dukcapilService->validateFormat($attributes['nik']);
            $attributes['is_valid_format'] = $isValidFormat;
        }

        return $attributes;
    }

    /**
     * Ekstrak teks dari citra KTP lokal (OCR non-AI)
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
     * Ekstrak atribut e-KTP dari teks
     */
    public function extractAttributesFromText(string $text): array
    {
        $data = [
            'nik' => null,
            'name' => null,
            'address' => null,
            'rt_rw' => null,
            'sub_district' => null,
            'district' => null,
            'city' => 'KOTA BAUBAU',
            'province' => 'SULAWESI TENGGARA',
            'confidence' => 0,
            'is_valid_format' => false,
            'raw_text_length' => strlen($text),
        ];

        if (empty(trim($text))) {
            return $data;
        }

        // 1. NIK (16 digit angka, utamakan kode provinsi 74 untuk Sultra/Baubau)
        if (preg_match('/\b(7472\d{12})\b/', $text, $m)) {
            $data['nik'] = $m[1];
            $data['confidence'] += 40;
        } elseif (preg_match('/\b(74\d{14})\b/', $text, $m)) {
            $data['nik'] = $m[1];
            $data['confidence'] += 35;
        } elseif (preg_match('/(?:NIK|No\.\s*KTP)\s*[:\.]?\s*(\d{16})\b/i', $text, $m)) {
            $data['nik'] = $m[1];
            $data['confidence'] += 30;
        } elseif (preg_match('/\b(\d{16})\b/', $text, $m)) {
            $data['nik'] = $m[1];
            $data['confidence'] += 20;
        }

        // 2. Nama Pemilik KTP
        if (preg_match('/(?:Nama|NAMA)\s*[:\.]?\s*([A-Z\s\.\',]{3,50})(?:\n|\r|Tempat|Jenis|Alamat|$)/i', $text, $m)) {
            $nameCandidate = trim($m[1]);
            // Filter noise kata kunci KTP
            if (!preg_match('/(TEMPAT|LAHIR|AGAMA|STATUS|GOLONGAN|DARAH)/i', $nameCandidate)) {
                $data['name'] = ucwords(strtolower($nameCandidate));
                $data['confidence'] += 25;
            }
        }

        // 3. Alamat (berhenti di akhir baris atau sebelum RT/RW)
        if (preg_match('/(?:Alamat|ALAMAT)\s*[:\.]?\s*([^\r\n]{3,60}?)(?:\r?\n|RT[\/\s]|RW[\/\s]|$)/i', $text, $m)) {
            $addr = trim($m[1]);
            $addr = preg_replace('/\s+(RT|RW|KEL|DESA).*$/i', '', $addr);
            if (!preg_match('/(AGAMA|STATUS|PEKERJAAN)/i', $addr)) {
                $data['address'] = ucwords(strtolower(trim($addr)));
                $data['confidence'] += 15;
            }
        }

        // 4. RT / RW
        if (preg_match('/(?:RT\s*\/\s*RW)\s*[:\.]?\s*([0-9]{1,3}\s*\/\s*[0-9]{1,3})/i', $text, $m)) {
            $data['rt_rw'] = trim($m[1]);
            $data['confidence'] += 10;
        }

        // 5. Kelurahan / Desa
        if (preg_match('/(?:Kel\/Desa|Kelurahan|Desa)\s*[:\.]?\s*([A-Z\s]{3,30})/i', $text, $m)) {
            $data['sub_district'] = ucwords(strtolower(trim($m[1])));
            $data['confidence'] += 10;
        }

        // 6. Kecamatan
        if (preg_match('/(?:Kecamatan)\s*[:\.]?\s*([A-Z\s]{3,30})/i', $text, $m)) {
            $data['district'] = ucwords(strtolower(trim($m[1])));
            $data['confidence'] += 10;
        }

        $data['confidence'] = min(100, $data['confidence']);

        return $data;
    }
}

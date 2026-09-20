<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class PbgDocumentParserService
{
    /**
     * Parse data dari file PBG/IMB (PDF atau Gambar)
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
            $text = $this->extractTextFromPdf($filePath);
        } else {
            // Untuk gambar, coba ekstrak metadata / text sederhana
            $text = $this->extractTextFromImage($filePath);
        }

        return $this->extractAttributesFromText($text);
    }

    /**
     * Ekstrak teks dari file PDF secara mandiri (pure PHP) dengan fallback CLI jika ada
     */
    public function extractTextFromPdf(string $filePath): string
    {
        // 1. Coba utilitas CLI pdftotext jika tersedia
        if (function_exists('shell_exec')) {
            $cmd = 'pdftotext ' . escapeshellarg($filePath) . ' - 2>/dev/null';
            $output = @shell_exec($cmd);
            if (!empty($output) && strlen(trim($output)) > 10) {
                return $output;
            }
        }

        // 2. Parser stream PDF mandiri (mendukung FlateDecode & operator Tj/TJ)
        $content = @file_get_contents($filePath);
        if (!$content) {
            return '';
        }

        $text = '';
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches);

        foreach ($matches[1] as $stream) {
            $data = $stream;
            $uncompressed = @gzuncompress($data);
            if ($uncompressed === false) {
                $uncompressed = @gzinflate(substr($data, 2));
            }
            if ($uncompressed !== false) {
                $data = $uncompressed;
            }

            // Ekstrak operator (text) Tj
            if (preg_match_all('/\(((?:[^\\\\\)]|\\\\.)*)\)\s*Tj/s', $data, $tjMatches)) {
                $text .= implode(' ', array_map([$this, 'cleanPdfString'], $tjMatches[1])) . "\n";
            }

            // Ekstrak operator [(t)(e)(x)(t)] TJ
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $data, $tjArrayMatches)) {
                foreach ($tjArrayMatches[1] as $arr) {
                    preg_match_all('/\(((?:[^\\\\\)]|\\\\.)*)\)/s', $arr, $sub);
                    $text .= implode('', array_map([$this, 'cleanPdfString'], $sub[1])) . ' ';
                }
                $text .= "\n";
            }
        }

        // Fallback: ambil string teks mentah jika stream decoding tidak menghasilkan apa-apa
        if (empty(trim($text))) {
            preg_match_all('/\(([a-zA-Z0-9\s\.,\-\/:\_]{3,})\)/', $content, $rawMatches);
            if (!empty($rawMatches[1])) {
                $text = implode(' ', $rawMatches[1]);
            }
        }

        return $text;
    }

    /**
     * Ekstrak teks dasar dari gambar (jika ada OCR / fallback exif)
     */
    protected function extractTextFromImage(string $filePath): string
    {
        // Jika ada tesseract di server
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
     * Bersihkan escape characters PDF string
     */
    protected function cleanPdfString(string $str): string
    {
        return str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r', '\\t'], ['(', ')', '\\', "\n", "\r", "\t"], $str);
    }

    /**
     * Mengekstrak entitas data dari teks dokumen PBG/IMB
     */
    public function extractAttributesFromText(string $text): array
    {
        $result = [
            'document_type' => 'UNKNOWN',
            'pbg_number' => null,
            'pbg_date' => null,
            'owner_name' => null,
            'owner_nik' => null,
            'building_address' => null,
            'building_area' => null,
            'ground_floor_area' => null,
            'land_area' => null,
            'building_floors' => null,
            'building_usage' => null,
            'raw_text_snippet' => mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 500),
            'confidence' => 0,
        ];

        if (empty(trim($text))) {
            return $result;
        }

        $detectedFields = 0;

        // 1. Deteksi Jenis Dokumen (PBG atau IMB)
        if (preg_match('/Persetujuan Bangunan Gedung|PBG|SIMBG/i', $text)) {
            $result['document_type'] = 'PBG';
        } elseif (preg_match('/Izin Mendirikan Bangunan|IMB/i', $text)) {
            $result['document_type'] = 'IMB';
        }

        // 2. Nomor PBG / IMB
        // Format contoh: PBG-747201-15092026-001 atau 640/123/IMB/DPMPTSP/2022 atau SK-PBG-7472...
        if (preg_match('/(?:Nomor|No\.?|Keputusan)[\s:-]*([A-Z0-9\/\.\-]+(?:PBG|IMB|SIMBG|DPMPTSP)[A-Z0-9\/\.\-]*)/i', $text, $m)) {
            $result['pbg_number'] = trim($m[1], " \t\n\r\0\x0B.,");
            $detectedFields++;
        } elseif (preg_match('/\b(PBG-[0-9A-Z\-]+)\b/i', $text, $m)) {
            $result['pbg_number'] = trim($m[1]);
            $detectedFields++;
        } elseif (preg_match('/\b(\d{3,4}\/[0-9A-Za-z\.\-\/]+\/(?:IMB|PBG)[0-9A-Za-z\.\-\/]*)\b/i', $text, $m)) {
            $result['pbg_number'] = trim($m[1]);
            $detectedFields++;
        }

        // 3. Tanggal Terbit
        // Format: 15 September 2026 atau 15/09/2026 atau 2026-09-15
        if (preg_match('/(?:Ditetapkan di|Tanggal|Terbit|Pada tanggal)[\s:-]*(?:Baubau,?\s*)?(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})/i', $text, $m)) {
            $result['pbg_date'] = trim($m[1]);
            $detectedFields++;
        } elseif (preg_match('/(?:Tanggal|Date)[\s:-]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})/i', $text, $m)) {
            $result['pbg_date'] = trim($m[1]);
            $detectedFields++;
        }

        // 4. Nama Pemilik / Pemohon
        if (preg_match('/(?:Nama Pemilik|Nama Pemohon|Diberikan kepada|Atas Nama)[\s:-]+([^\n\r]+)/i', $text, $m)) {
            $name = trim($m[1], " \t\n\r\0\x0B:,.");
            $name = preg_replace('/(?:\bNIK\b|Nomor Induk|Alamat).*$/i', '', $name);
            $name = trim($name, " \t\n\r\0\x0B:,.");
            // Filter common header noise
            if (!empty($name) && !preg_match('/Pemerintah|Dinas|Kepala|Bupati|Walikota/i', $name)) {
                $result['owner_name'] = $name;
                $detectedFields++;
            }
        }

        // 5. NIK Pemilik (jika tercantum di dokumen PBG)
        if (preg_match('/(?:NIK|Nomor Induk Kependudukan)[\s:-]*([0-9]{16})\b/i', $text, $m)) {
            $result['owner_nik'] = trim($m[1]);
            $detectedFields++;
        }

        // 6. Alamat / Lokasi Bangunan
        if (preg_match('/(?:Lokasi Bangunan|Alamat Objek|Terletak di|Alamat Persil)[\s:-]+([^\n\r]{5,120})(?:\n|\r|Kecamatan|Kelurahan|Fungsi|Luas)/i', $text, $m)) {
            $result['building_address'] = trim($m[1], " \t\n\r\0\x0B:,.");
            $detectedFields++;
        }

        // 7. Luas Bangunan (m2)
        // Format: Luas Bangunan : 140 m2 atau Luas Total Bangunan : 140,50 m²
        if (preg_match('/(?:Luas Total(?: Bangunan)?|Luas Bangunan)[\s:-]*([\d\.,]+)\s*(?:m2|m²|meter)/i', $text, $m)) {
            $val = str_replace(['.', ','], ['', '.'], trim($m[1]));
            if (is_numeric($val) && (float)$val > 0) {
                $result['building_area'] = (float)$val;
                $detectedFields++;
            }
        }

        // 8. Luas Lantai Dasar (KDB)
        if (preg_match('/(?:Luas Lantai Dasar|Luas Dasar)[\s:-]*([\d\.,]+)\s*(?:m2|m²|meter)/i', $text, $m)) {
            $val = str_replace(['.', ','], ['', '.'], trim($m[1]));
            if (is_numeric($val) && (float)$val > 0) {
                $result['ground_floor_area'] = (float)$val;
            }
        }

        // 9. Luas Tanah (jika tercantum)
        if (preg_match('/(?:Luas Tanah|Luas Persil)[\s:-]*([\d\.,]+)\s*(?:m2|m²|meter)/i', $text, $m)) {
            $val = str_replace(['.', ','], ['', '.'], trim($m[1]));
            if (is_numeric($val) && (float)$val > 0) {
                $result['land_area'] = (float)$val;
            }
        }

        // 10. Jumlah Lantai
        // Format: 2 Lantai atau Bertingkat 2
        if (preg_match('/(?:Jumlah Lantai|Tinggi Bangunan|Lantai)[\s:-]*(\d{1,2})\s*(?:Lantai)?/i', $text, $m)) {
            $result['building_floors'] = (int)$m[1];
            $detectedFields++;
        }

        // 11. Fungsi Bangunan
        if (preg_match('/(?:Fungsi Bangunan|Penggunaan Bangunan|Fungsi)[\s:-]+(Fungsi Hunian|Hunian|Rumah Tinggal|Tempat Usaha|Usaha|Komersial|Gudang|Sosial dan Budaya|Keagamaan|Khusus|Campuran)/i', $text, $m)) {
            $usage = trim($m[1]);
            // Normalisasi ke opsi aplikasi
            if (preg_match('/Hunian|Rumah Tinggal/i', $usage)) {
                $result['building_usage'] = 'Rumah Tinggal';
            } elseif (preg_match('/Usaha|Komersial/i', $usage)) {
                $result['building_usage'] = 'Tempat Usaha';
            } elseif (preg_match('/Gudang/i', $usage)) {
                $result['building_usage'] = 'Gudang';
            } else {
                $result['building_usage'] = $usage;
            }
            $detectedFields++;
        }

        // Hitung confidence score
        $result['confidence'] = min(100, (int) round(($detectedFields / 6) * 100));

        return $result;
    }
}

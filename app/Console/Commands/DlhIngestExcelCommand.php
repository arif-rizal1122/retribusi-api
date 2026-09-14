<?php

namespace App\Console\Commands;

use App\Models\DlhIngestLog;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;
use SimpleXMLElement;

class DlhIngestExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpad:dlh-ingest-excel 
                            {--path= : Path to folder containing .xlsx files or a single .xlsx file}
                            {--dry-run : Simulate ingestion without saving to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ETL Ingestion tool for DLH juru pungut spreadsheets into property-centric tax_objects';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->option('path') ?: storage_path('app/dlh_spreadsheets');
        $isDryRun = $this->option('dry-run');

        if (!file_exists($path)) {
            $this->error("Path tidak ditemukan: {$path}");
            return 1;
        }

        $files = is_dir($path) 
            ? glob(rtrim($path, '/') . '/*.xlsx')
            : [$path];

        if (empty($files)) {
            $this->warn("Tidak ditemukan file .xlsx pada: {$path}");
            return 0;
        }

        $this->info("Ditemukan " . count($files) . " file spreadsheet DLH.");
        if ($isDryRun) {
            $this->warn("MODE SIMULASI (DRY-RUN) AKTIF - Database tidak akan diubah.");
        }

        $grandTotalRows = 0;
        $grandTotalIngested = 0;
        $grandTotalQuarantined = 0;

        foreach ($files as $fileIndex => $file) {
            $filename = basename($file);
            $this->line("<fg=cyan>[" . ($fileIndex + 1) . "/" . count($files) . "] Memproses file:</> {$filename}");

            $rows = $this->parseXlsx($file);
            if (empty($rows)) {
                $this->warn("  -> File kosong atau tidak dapat di-parse: {$filename}");
                continue;
            }

            // Temukan header
            $headerRowIndex = 0;
            $header = [];
            foreach ($rows as $idx => $r) {
                $joined = strtolower(implode(' ', $r));
                if (str_contains($joined, 'nama') || str_contains($joined, 'alamat') || str_contains($joined, 'kelurahan')) {
                    $headerRowIndex = $idx;
                    $header = array_map('trim', $r);
                    break;
                }
            }

            if (empty($header)) {
                $header = array_map('trim', $rows[0] ?? []);
                $headerRowIndex = 0;
            }

            $dataRows = array_slice($rows, $headerRowIndex + 1);
            $this->line("  -> Jumlah baris data: " . count($dataRows));

            $fileIngested = 0;
            $fileQuarantined = 0;

            foreach ($dataRows as $rIndex => $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip baris kosong
                }

                $grandTotalRows++;
                $mapped = $this->mapRow($header, $row);

                if (empty($mapped['name'])) {
                    continue;
                }

                // Cek anomali koordinat
                $hasCoords = !empty($mapped['latitude']) && !empty($mapped['longitude']);
                $anomalyReason = null;
                $status = 'SUCCESS';

                if (!$hasCoords) {
                    $status = 'QUARANTINE_MISSING_COORDS';
                    $anomalyReason = 'Koordinat spasial belum tersedia / kosong pada spreadsheet juru pungut';
                    $fileQuarantined++;
                    $grandTotalQuarantined++;
                }

                if ($isDryRun) {
                    $this->line("    [DRY-RUN] {$status} - {$mapped['name']} ({$mapped['address']})");
                    continue;
                }

                DB::transaction(function () use ($filename, $rIndex, $mapped, $status, $anomalyReason, &$fileIngested, &$grandTotalIngested) {
                    // Cari atau buat Taxpayer dummy/kelurahan jika belum ada
                    $taxpayer = Taxpayer::withoutGlobalScopes()->firstOrCreate(
                        [
                            'opd_id' => 4,
                            'name' => $mapped['name'],
                        ],
                        [
                            'phone' => $mapped['phone'] ?? null,
                            'address' => $mapped['address'] ?? 'Kota Baubau',
                            'is_active' => true,
                        ]
                    );

                    // Buat TaxObject property-centric
                    $taxObject = TaxObject::withoutGlobalScopes()->create([
                        'taxpayer_id' => $taxpayer->id,
                        'opd_id' => 4, // DLH
                        'name' => $mapped['name'],
                        'address' => $mapped['address'] ?? 'Kota Baubau',
                        'tipe_penghuni' => $mapped['tipe_penghuni'] ?? 'PEMILIK',
                        'status_hunian' => $mapped['status_hunian'] ?? 'BERPENGHUNI',
                        'building_category' => $mapped['building_category'] ?? 'RUMAH_SEDERHANA',
                        'penanggung_jawab_nama' => $mapped['penanggung_jawab_nama'] ?? $mapped['name'],
                        'penanggung_jawab_kontak' => $mapped['phone'] ?? null,
                        'latitude' => $mapped['latitude'] ?? null,
                        'longitude' => $mapped['longitude'] ?? null,
                        'is_active' => true,
                    ]);

                    // Catat ke log karantina/ingest
                    DlhIngestLog::create([
                        'source_file' => $filename,
                        'row_index' => $rIndex + 1,
                        'raw_data' => $mapped,
                        'status' => $status,
                        'anomaly_reason' => $anomalyReason,
                        'tax_object_id' => $taxObject->id,
                    ]);

                    $fileIngested++;
                    $grandTotalIngested++;
                });
            }

            $this->info("  -> Hasil: {$fileIngested} berhasil di-ingest, {$fileQuarantined} masuk karantina (missing coords).");
        }

        $this->newLine();
        $this->info("=== RINGKASAN ETL INGESTION DLH ===");
        $this->info("Total Baris Diproses: {$grandTotalRows}");
        $this->info("Total Berhasil: {$grandTotalIngested}");
        $this->warn("Total Karantina (Validasi/Koordinat): {$grandTotalQuarantined}");

        return 0;
    }

    /**
     * Parsing file XLSX tanpa library eksternal menggunakan ZipArchive & SimpleXML
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Baca shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca worksheet pertama
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $attr = $cell->attributes();
                $type = isset($attr['t']) ? (string) $attr['t'] : 'n';
                $value = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $val = $sharedStrings[(int) $value];
                } else {
                    $val = $value;
                }

                $rowData[] = $val;
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Map baris data mentah ke atribut terstandarisasi DLH M-PAD
     */
    protected function mapRow(array $header, array $row): array
    {
        $mapped = [
            'name' => null,
            'address' => null,
            'phone' => null,
            'kelurahan' => null,
            'tipe_penghuni' => 'PEMILIK',
            'status_hunian' => 'BERPENGHUNI',
            'building_category' => 'RUMAH_SEDERHANA',
            'penanggung_jawab_nama' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        foreach ($row as $colIdx => $val) {
            $h = strtolower($header[$colIdx] ?? '');
            $val = trim($val);

            if (str_contains($h, 'nama') || str_contains($h, 'pelanggan') || str_contains($h, 'wajib')) {
                if (!$mapped['name']) $mapped['name'] = $val;
            } elseif (str_contains($h, 'alamat') || str_contains($h, 'jalan')) {
                $mapped['address'] = $val;
            } elseif (str_contains($h, 'kelurahan') || str_contains($h, 'desa')) {
                $mapped['kelurahan'] = $val;
            } elseif (str_contains($h, 'hp') || str_contains($h, 'telp') || str_contains($h, 'kontak')) {
                $mapped['phone'] = $val;
            } elseif (str_contains($h, 'lat') || str_contains($h, 'lintang')) {
                $mapped['latitude'] = is_numeric($val) ? (float) $val : null;
            } elseif (str_contains($h, 'long') || str_contains($h, 'bujur')) {
                $mapped['longitude'] = is_numeric($val) ? (float) $val : null;
            } elseif (str_contains($h, 'kategori') || str_contains($h, 'golongan') || str_contains($h, 'tarif')) {
                $cat = strtoupper($val);
                if (str_contains($cat, 'MEWAH') || str_contains($cat, 'R3')) {
                    $mapped['building_category'] = 'RUMAH_MEWAH';
                } elseif (str_contains($cat, 'MENENGAH') || str_contains($cat, 'R2')) {
                    $mapped['building_category'] = 'RUMAH_MENENGAH';
                } elseif (str_contains($cat, 'NIAGA') || str_contains($cat, 'KOMERSIAL') || str_contains($cat, 'RUKO')) {
                    $mapped['building_category'] = 'NIAGA_KECIL';
                } else {
                    $mapped['building_category'] = 'RUMAH_SEDERHANA';
                }
            } elseif (str_contains($h, 'tipe') || str_contains($h, 'status pen')) {
                $mapped['tipe_penghuni'] = str_contains(strtoupper($val), 'SEWA') ? 'PENYEWA' : 'PEMILIK';
            }
        }

        if ($mapped['kelurahan'] && $mapped['address']) {
            $mapped['address'] .= ', Kel. ' . $mapped['kelurahan'];
        }

        return $mapped;
    }
}

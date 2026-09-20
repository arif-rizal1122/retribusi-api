<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PbbNopApplication;
use App\Models\TaxObject;
use App\Services\DukcapilNikService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PbbNopApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = PbbNopApplication::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Warga (Taxpayer) hanya melihat aplikasi miliknya; admin/bapenda melihat semua
        if ($request->user() && $request->user() instanceof \App\Models\Taxpayer) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Endpoint pembacaan cerdas berkas PBG / IMB (Smart Document Reader)
     * POST /api/citizen/pbb/parse-pbg-document
     */
    public function parsePbgDocument(Request $request, \App\Services\PbgDocumentParserService $parser)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120',
        ], [
            'file.required' => 'Silakan pilih berkas dokumen PBG atau IMB untuk dibaca.',
            'file.mimes' => 'Format berkas harus PDF, JPG, PNG, atau WEBP.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $parsed = $parser->parse($request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen PBG/IMB berhasil dibaca.',
                'data' => $parsed,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error parsing PBG document: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca dokumen: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint pembacaan cerdas berkas Sertifikat Tanah BPN (SHM / HGB)
     * POST /api/citizen/pbb/parse-certificate-document
     */
    public function parseCertificateDocument(Request $request, \App\Services\PbbCertificateParserService $parser)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120',
        ], [
            'file.required' => 'Silakan pilih berkas sertifikat tanah untuk dibaca.',
            'file.mimes' => 'Format berkas harus PDF, JPG, PNG, atau WEBP.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $parsed = $parser->parse($request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen sertifikat tanah berhasil dibaca.',
                'data' => $parsed,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error parsing Certificate document: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca dokumen sertifikat: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint pembacaan cerdas berkas e-KTP Pemilik Asli
     * POST /api/citizen/pbb/parse-ktp-document
     */
    public function parseKtpDocument(Request $request, \App\Services\PbbKtpParserService $parser)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120',
        ], [
            'file.required' => 'Silakan pilih berkas e-KTP untuk dibaca.',
            'file.mimes' => 'Format berkas harus PDF, JPG, PNG, atau WEBP.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $parsed = $parser->parse($request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen e-KTP berhasil dibaca.',
                'data' => $parsed,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error parsing KTP document: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca dokumen KTP: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint integrasi ATR/BPN BHUMI untuk pencarian data tanah
     * GET /api/citizen/pbb/bpn-lookup
     */
    public function bpnLookup(Request $request, \App\Services\BhumiService $bhumiService)
    {
        $nib = $request->query('nib');
        $lat = $request->query('latitude');
        $lng = $request->query('longitude');

        $result = [
            'is_matched' => false,
            'nib' => $nib,
            'nop' => null,
            'land_area' => null,
            'znt' => null,
            'nir' => null,
            'verification_badge' => 'Pendaftaran Mandiri',
            'message' => 'Data bidang tanah belum terpetakan di ATR/BPN BHUMI.',
        ];

        // 1. Cek NibNopMapping lokal jika ada NIB
        if ($nib) {
            $cleanNib = preg_replace('/[^0-9]/', '', $nib);
            $mapping = \App\Models\NibNopMapping::where('nib', $cleanNib)->first();
            if ($mapping) {
                $result['is_matched'] = true;
                $result['nib'] = $mapping->nib;
                $result['nop'] = $mapping->nop;
                $result['land_area'] = $mapping->land_area_m2 ? (float) $mapping->land_area_m2 : null;
                $result['znt'] = $mapping->znt;
                $result['taxpayer_name'] = $mapping->taxpayer_name;
                $result['verification_badge'] = '✓ Terverifikasi ATR/BPN BHUMI';
                $result['message'] = 'Data bidang tanah terverifikasi dari sinkronisasi BPN BHUMI.';

                return response()->json(['status' => 'success', 'data' => $result]);
            }
        }

        // 2. Spatial lookup via BhumiService jika ada titik koordinat
        if ($lat && $lng && is_numeric($lat) && is_numeric($lng)) {
            $spatial = $bhumiService->reverseLookupSpatial((float) $lat, (float) $lng);
            if (!empty($spatial['is_matched'])) {
                $result['is_matched'] = true;
                $result['nib'] = $spatial['nib'] ?? $nib;
                $result['nop'] = $spatial['nop_bpn'] ?? null;
                $result['land_area'] = $spatial['legal_land_area'] ?? null;
                $result['znt'] = $spatial['zona_znt'] ?? null;
                $result['nir'] = $spatial['nir_value'] ?? null;
                $result['verification_badge'] = $spatial['verification_badge'] ?? '✓ Terverifikasi ATR/BPN BHUMI';
                $result['message'] = 'Bidang tanah terverifikasi dari koordinat spasial BPN.';

                return response()->json(['status' => 'success', 'data' => $result]);
            }
        }

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    public function store(Request $request)
    {
        $isOther = $request->input('registered_for') === 'other';
        $hasBuilding = $request->boolean('has_building') || (float) $request->input('building_area', 0) > 0;

        $rules = [
            'nik' => 'required|string|max:16',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'land_area' => 'required|numeric|min:1',
            'has_building' => 'nullable|boolean',
            'building_area' => $hasBuilding ? 'required|numeric|min:1' : 'nullable|numeric|min:0',
            'building_floors' => 'nullable|integer|min:1',
            'building_usage' => 'nullable|string|max:100',
            'pbg_number' => 'nullable|string|max:100',
            'pbg_date' => 'nullable|string|max:50',
            'akte_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120',
            'registered_for' => 'sometimes|in:self,other',
            'nop' => 'nullable|string|max:20',
            'metadata' => 'nullable',
            'nib' => 'nullable|string|max:50',
            'znt_applied' => 'nullable|string|max:20',
            'nir_applied' => 'nullable|numeric',
            'is_bpn_verified' => 'nullable|boolean',
            'polygon_coordinates' => 'nullable',
        ];

        // Validasi khusus tab "Untuk Orang Lain"
        if ($isOther) {
            $rules['owner_nik'] = 'required|string|size:16';
            $rules['owner_name'] = 'required|string|max:255';
            $rules['owner_address'] = 'nullable|string';
            $rules['ktp_file'] = 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
            $rules['surat_kuasa_file'] = 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
        } else {
            $rules['owner_nik'] = 'nullable|string|max:16';
            $rules['owner_name'] = 'nullable|string|max:255';
            $rules['owner_address'] = 'nullable|string';
            $rules['ktp_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
            $rules['surat_kuasa_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
        }

        // Dokumen PBG/IMB wajib jika ada bangunan (kecuali fasilitas umum)
        $isFasum = $request->boolean('is_fasum');
        if ($hasBuilding && ! $isFasum) {
            $rules['imb_file'] = 'required|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
        } else {
            $rules['imb_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $friendly = [];
            $maxMb = 5;

            $fileFields = ['ktp_file', 'akte_file', 'imb_file', 'pbg_file', 'surat_kuasa_file'];
            $labels = [
                'ktp_file' => 'Foto KTP Pemilik Asli',
                'surat_kuasa_file' => 'Surat Kuasa / Keterangan Waris',
                'akte_file' => 'Dokumen Akte / Sertifikat Tanah',
                'imb_file' => 'Dokumen PBG / IMB',
                'nik' => 'NIK Pemohon',
                'name' => 'Nama Pemohon',
                'address' => 'Alamat Objek Pajak',
                'land_area' => 'Luas Tanah',
                'building_area' => 'Luas Bangunan',
                'owner_nik' => 'NIK Pemilik Asli',
                'owner_name' => 'Nama Pemilik Asli',
                'owner_address' => 'Alamat Pemilik Asli',
                'pbg_number' => 'Nomor PBG / IMB',
            ];

            foreach ($errors as $field => $msgs) {
                $fname = $labels[$field] ?? $field;
                $isFile = in_array($field, $fileFields, true);
                $msg = $msgs[0] ?? 'tidak valid';

                if ($isFile) {
                    $ext = $request->hasFile($field)
                        ? strtolower((string) $request->file($field)->getClientOriginalExtension())
                        : '';
                    if (str_contains($msg, 'mimes') || str_contains($msg, 'mime') || str_contains($msg, 'of type')) {
                        $friendly[$field] = $fname.' formatnya '.($ext ? strtoupper($ext) : 'tidak dikenal').
                            '. Format yang diterima: PDF, JPG, JPEG, PNG, WEBP, HEIC, HEIF.';
                    } elseif (str_contains($msg, 'required')) {
                        $friendly[$field] = $fname.' wajib diunggah. Silakan pilih file (PDF/JPG/PNG, maksimal '.$maxMb.' MB).';
                    } elseif (str_contains($msg, 'max') || str_contains($msg, 'upload')) {
                        $friendly[$field] = $fname.' terlalu besar. Maksimal '.$maxMb.' MB. Silakan kompres file.';
                    } else {
                        $friendly[$field] = $fname.' '.$msg;
                    }
                } else {
                    if (str_contains($msg, 'required')) {
                        $friendly[$field] = $fname.' wajib diisi.';
                    } elseif (str_contains($msg, 'size')) {
                        $friendly[$field] = $fname.' harus berjumlah 16 digit angka.';
                    } else {
                        $friendly[$field] = $fname.' '.$msg;
                    }
                }
            }

            return response()->json([
                'message' => 'Ada '.count($friendly).' data yang perlu diperbaiki.',
                'errors' => $friendly,
            ], 422);
        }

        // Validasi format NIK 16 digit + H2H Dukcapil (default not_configured → tidak memblokir)
        $dukcapilService = app(DukcapilNikService::class);

        if (! $dukcapilService->validateFormat($request->nik)) {
            return response()->json([
                'message' => 'Format NIK tidak valid.',
                'errors' => ['nik' => ['Format NIK tidak valid.']],
            ], 422);
        }

        if ($dukcapilService->available()) {
            $dukcapil = $dukcapilService->validate($request->nik, $request->name);
            if (($dukcapil['valid'] ?? false) !== true) {
                $reason = $dukcapil['reason'] ?? 'NIK tidak valid menurut Dukcapil.';
                return response()->json([
                    'message' => $reason,
                    'errors' => ['nik' => [$reason]],
                ], 422);
            }
        } else {
            Log::info('DUKCAPIL not configured, NIK validation skipped (nop application)', [
                'nik' => substr($request->nik, 0, 6).'****',
            ]);
        }

        $ktpPath = $request->hasFile('ktp_file')
            ? $request->file('ktp_file')->store('pbb_applications/ktp', 'public')
            : 'verified_via_account';
        $suratKuasaPath = $request->hasFile('surat_kuasa_file')
            ? $request->file('surat_kuasa_file')->store('pbb_applications/surat_kuasa', 'public')
            : null;
        $aktePath = $request->file('akte_file')->store('pbb_applications/akte', 'public');
        $imbPath = $request->hasFile('imb_file') ? $request->file('imb_file')->store('pbb_applications/imb', 'public') : null;

        $registeredFor = $request->input('registered_for', 'self');

        // Prepare metadata
        $metadata = $request->input('metadata', []);
        if (! is_array($metadata)) {
            $metadata = json_decode((string) $metadata, true) ?? [];
        }
        if ($request->filled('owner_nik')) {
            $metadata['owner_nik'] = $request->input('owner_nik');
        }
        if ($suratKuasaPath) {
            $metadata['surat_kuasa_file_path'] = $suratKuasaPath;
        }
        if ($request->filled('pbg_number')) {
            $metadata['pbg_number'] = $request->input('pbg_number');
        }
        if ($request->filled('pbg_date')) {
            $metadata['pbg_date'] = $request->input('pbg_date');
        }
        $metadata['has_building'] = $hasBuilding;
        if ($request->filled('building_floors')) {
            $metadata['building_floors'] = $request->input('building_floors');
        }
        if ($request->filled('building_usage')) {
            $metadata['building_usage'] = $request->input('building_usage');
        }
        if ($request->has('is_fasum')) {
            $metadata['is_fasum'] = $request->boolean('is_fasum');
        }

        $polyCoords = $request->input('polygon_coordinates');
        if (is_string($polyCoords)) {
            $decoded = json_decode($polyCoords, true);
            if (is_array($decoded)) {
                $polyCoords = $decoded;
            }
        }

        $application = PbbNopApplication::create([
            'user_id' => $request->user() ? $request->user()->id : null,
            'nik' => $request->nik,
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'land_area' => $request->land_area,
            'building_area' => $hasBuilding ? $request->building_area : 0,
            'ktp_file_path' => $ktpPath,
            'akte_file_path' => $aktePath,
            'imb_file_path' => $imbPath,
            'status' => 'PENDING',
            'registered_for' => $registeredFor,
            'owner_name' => $registeredFor === 'other' ? $request->owner_name : null,
            'owner_address' => $registeredFor === 'other' ? ($request->owner_address ?? $request->address) : null,
            'nop' => $request->input('nop'),
            'metadata' => ! empty($metadata) ? $metadata : null,
            'nib' => $request->input('nib'),
            'znt_applied' => $request->input('znt_applied'),
            'nir_applied' => $request->input('nir_applied'),
            'polygon_coordinates' => $polyCoords,
            'is_bpn_verified' => $request->boolean('is_bpn_verified'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully',
            'data' => $application,
        ], 201);
    }

    public function show($id)
    {
        $application = PbbNopApplication::findOrFail($id);
        return response()->json($application);
    }

    public function updateStatus(Request $request, $id)
    {
        $application = PbbNopApplication::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:PENDING,SURVEY,APPROVED,REJECTED',
            'survey_notes' => 'nullable|string',
            'survey_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'nop' => 'nullable|required_if:status,APPROVED|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Maker-checker: pemohon tidak boleh memutuskan status permohonannya sendiri.
        if (in_array($request->status, ['APPROVED', 'REJECTED'], true)
            && $application->user_id
            && (int) $application->user_id === (int) $request->user()->id) {
            return response()->json([
                'message' => 'Pemohon tidak boleh memutuskan status permohonannya sendiri (maker-checker).',
            ], 403);
        }

        if ($request->status === 'APPROVED' && $request->filled('nop')) {
            $nop = $request->nop;

            if (TaxObject::where(function ($q) use ($nop) {
                $q->where('nop', $nop)->orWhere('metadata->nop_pbb', $nop);
            })->exists()) {
                return response()->json([
                    'errors' => ['nop' => ['NOP sudah digunakan pada objek pajak lain.']],
                ], 422);
            }

            if (PbbNopApplication::where('nop', $nop)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'errors' => ['nop' => ['NOP sudah digunakan pada aplikasi lain.']],
                ], 422);
            }
        }

        $application->status = $request->status;
        
        if ($request->has('survey_notes')) {
            $application->survey_notes = $request->survey_notes;
        }

        if ($request->hasFile('survey_photo')) {
            if ($application->survey_photo_path) {
                Storage::disk('public')->delete($application->survey_photo_path);
            }
            $application->survey_photo_path = $request->file('survey_photo')->store('pbb_applications/survey', 'public');
        }

        if ($request->status === 'APPROVED' && $request->has('nop')) {
            $application->nop = $request->nop;
        }

        $application->save();

        return response()->json(['message' => 'Status updated successfully', 'data' => $application]);
    }
}

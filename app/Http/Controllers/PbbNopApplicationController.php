<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PbbNopApplication;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\TaxpayerPbbObject;
use App\Models\SpptRecord;
use App\Models\Bill;
use App\Services\BillCreationService;
use App\Services\PbbCalculationService;
use App\Services\DukcapilNikService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PbbNopApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = PbbNopApplication::query();

        if ($request->filled('status')) {
            if (is_array($request->status)) {
                $statuses = array_map('strtoupper', $request->status);
                $query->whereIn('status', $statuses);
            } elseif (str_contains($request->status, ',')) {
                $statuses = array_map('strtoupper', explode(',', $request->status));
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', strtoupper($request->status));
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('nib', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('nop', 'like', "%{$search}%")
                    ->orWhere('metadata->certificate_number', 'like', "%{$search}%")
                    ->orWhere('metadata->pbg_number', 'like', "%{$search}%");
            });
        }

        // Warga (Taxpayer) hanya melihat aplikasi miliknya; admin/bapenda melihat semua
        if ($request->user() && $request->user() instanceof \App\Models\Taxpayer) {
            $query->where('user_id', $request->user()->id);
        }

        $perPage = (int) $request->get('per_page', 25);
        return response()->json($query->latest()->paginate($perPage));
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

        // Dokumen PBG/IMB dibuat opsional (banyak warga belum memiliki PBG saat pendaftaran awal)
        $rules['imb_file'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:5120';
        $rules['building_photo'] = 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:5120';
        $rules['building_photo_file'] = 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:5120';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $friendly = [];
            $maxMb = 5;

            $fileFields = ['ktp_file', 'akte_file', 'imb_file', 'pbg_file', 'surat_kuasa_file', 'building_photo', 'building_photo_file'];
            $labels = [
                'ktp_file' => 'Foto KTP Pemilik Asli',
                'surat_kuasa_file' => 'Surat Kuasa / Keterangan Waris',
                'akte_file' => 'Dokumen Akte / Sertifikat Tanah',
                'imb_file' => 'Dokumen PBG / IMB',
                'building_photo' => 'Foto Fisik Bangunan',
                'building_photo_file' => 'Foto Fisik Bangunan',
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

        $buildingPhotoPath = null;
        if ($request->hasFile('building_photo')) {
            $buildingPhotoPath = $request->file('building_photo')->store('pbb_applications/building_photos', 'public');
        } elseif ($request->hasFile('building_photo_file')) {
            $buildingPhotoPath = $request->file('building_photo_file')->store('pbb_applications/building_photos', 'public');
        }

        $registeredFor = $request->input('registered_for', 'self');

        // Prepare metadata
        $metadata = $request->input('metadata', []);
        if (! is_array($metadata)) {
            $metadata = json_decode((string) $metadata, true) ?? [];
        }
        if ($buildingPhotoPath) {
            $metadata['building_photo_path'] = $buildingPhotoPath;
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
            'survey_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
            'nop' => 'nullable|required_if:status,APPROVED|string|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Maker-checker: pemohon warga tidak boleh memutuskan status permohonannya sendiri.
        if (in_array($request->status, ['APPROVED', 'REJECTED'], true)
            && $application->user_id
            && $request->user() instanceof \App\Models\Taxpayer
            && (int) $application->user_id === (int) $request->user()->id) {
            return response()->json([
                'message' => 'Pemohon tidak boleh memutuskan status permohonannya sendiri (maker-checker).',
            ], 403);
        }

        // Hanya admin, super_admin, opd Bapenda, verifikator, atau kepala_bapenda yang berwenang menyetujui / menolak NOP resmi
        $allowedReviewRoles = ['admin', 'super_admin', 'opd', 'verifikator', 'kepala_bapenda'];
        if (in_array($request->status, ['APPROVED', 'REJECTED'], true)
            && (!isset($request->user()->role) || !in_array($request->user()->role, $allowedReviewRoles, true))) {
            return response()->json([
                'message' => 'Hanya Petugas/Admin Bapenda yang berwenang memutuskan persetujuan NOP resmi.',
            ], 403);
        }

        $nop = null;
        if ($request->status === 'APPROVED' && $request->filled('nop')) {
            $digitsOnly = preg_replace('/[^0-9]/', '', (string) $request->nop);
            $nop = (strlen($digitsOnly) === 18) ? $digitsOnly : trim($request->nop);

            if (TaxObject::where(function ($q) use ($nop, $digitsOnly) {
                $q->where('nop', $nop)
                    ->orWhere('nop', $digitsOnly)
                    ->orWhere('metadata->nop_pbb', $nop)
                    ->orWhere('metadata->nop_pbb', $digitsOnly);
            })->exists()) {
                return response()->json([
                    'errors' => ['nop' => ['NOP sudah digunakan pada objek pajak lain.']],
                ], 422);
            }

            if (PbbNopApplication::where(function ($q) use ($nop, $digitsOnly) {
                $q->where('nop', $nop)->orWhere('nop', $digitsOnly);
            })->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'errors' => ['nop' => ['NOP sudah digunakan pada aplikasi lain.']],
                ], 422);
            }
        }

        DB::transaction(function () use ($application, $nop, $request) {
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

            if ($request->status === 'APPROVED' && $nop) {
                $application->nop = $nop;
            }

            // Simpan audit trail metadata: Rekomendasi survei, penugasan petugas, dan alasan penolakan
            $metadata = is_array($application->metadata) ? $application->metadata : [];
            
            if ($request->filled('survey_recommendation')) {
                $metadata['survey_recommendation'] = $request->input('survey_recommendation');
                $metadata['surveyed_at'] = now()->toIso8601String();
                $metadata['surveyor_id'] = $request->user()?->id;
                $metadata['surveyor_name'] = $request->user()?->name;
            }
            if ($request->filled('survey_physical_condition')) {
                $metadata['survey_physical_condition'] = $request->input('survey_physical_condition');
            }
            if ($request->has('survey_location_match')) {
                $metadata['survey_location_match'] = filter_var($request->input('survey_location_match'), FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->filled('assigned_petugas_id')) {
                $metadata['assigned_petugas_id'] = $request->input('assigned_petugas_id');
                $metadata['assigned_petugas_name'] = $request->input('assigned_petugas_name');
                $metadata['assigned_at'] = now()->toIso8601String();
            }
            if ($request->filled('rejection_reason')) {
                $metadata['rejection_reason'] = $request->input('rejection_reason');
            }
            if ($request->filled('verification_checklist')) {
                $checklist = $request->input('verification_checklist');
                $metadata['verification_checklist'] = is_array($checklist) ? $checklist : json_decode((string)$checklist, true);
            }

            $application->metadata = !empty($metadata) ? $metadata : null;
            $application->save();

            if ($request->status === 'APPROVED' && $nop) {
                $this->autoLinkAndGeneratePbbBill($application, $nop);
            }
        });

        return response()->json(['message' => 'Status updated successfully', 'data' => $application->fresh()]);
    }

    /**
     * Otomatis menautkan NOP ke akun Wajib Pajak, mendaftarkan Objek Pajak (TaxObject),
     * sinkronisasi SpptRecord, dan menerbitkan Tagihan (Bill) SPPT PBB tahun berjalan.
     */
    private function autoLinkAndGeneratePbbBill(PbbNopApplication $application, string $nop): void
    {
        // 1. Tentukan / Cari Wajib Pajak
        $bapendaOpd = \App\Models\Opd::where('id', 5)->orWhere('name', 'like', '%Bapenda%')->first()
            ?? \App\Models\Opd::first();
        $opdId = $bapendaOpd?->id;

        $pbbType = \App\Models\RetributionType::where('id', 16)->orWhere('name', 'like', '%PBB%')->first()
            ?? \App\Models\RetributionType::first();
        $retributionTypeId = $pbbType?->id;

        $taxpayer = null;
        if ($application->user_id) {
            $taxpayer = Taxpayer::find($application->user_id);
        }
        if (! $taxpayer && $application->nik) {
            $taxpayer = Taxpayer::where('nik', $application->nik)->first();
        }
        if (! $taxpayer) {
            $taxpayer = Taxpayer::create([
                'nik' => $application->nik,
                'name' => $application->name,
                'address' => $application->address,
                'opd_id' => $opdId,
            ]);
        }

        // 2. Link ke TaxpayerPbbObject (agar langsung muncul di Objek Saya warga)
        TaxpayerPbbObject::firstOrCreate(
            ['taxpayer_id' => $taxpayer->id, 'nop' => $nop],
            [
                'name_on_sppt' => $application->name,
                'address_on_sppt' => $application->address,
                'kelurahan' => $application->metadata['kelurahan'] ?? null,
                'kota' => $application->metadata['kota'] ?? 'Kota Baubau',
                'is_verified' => true,
                'notes' => 'Otomatis dari persetujuan permohonan NOP #'.$application->id,
            ]
        );

        if (! $opdId || ! $retributionTypeId) {
            return;
        }

        // 3. Daftarkan TaxObject (Objek Pajak PBB-P2)
        $taxObject = TaxObject::firstOrCreate(
            ['nop' => $nop, 'taxpayer_id' => $taxpayer->id],
            [
                'retribution_type_id' => $retributionTypeId,
                'opd_id' => $opdId,
                'name' => 'Objek PBB '.$nop.' - '.$application->name,
                'address' => $application->address,
                'latitude' => $application->latitude,
                'longitude' => $application->longitude,
                'status' => 'active',
                'metadata' => [
                    'nop_pbb' => $nop,
                    'tahun_pajak' => (string) date('Y'),
                    'nama_wp' => $application->name,
                    'alamat_wp' => $application->address,
                    'land_area' => (float) $application->land_area,
                    'building_area' => (float) $application->building_area,
                    'nib' => $application->nib,
                    'znt' => $application->znt_applied,
                    'nir' => $application->nir_applied,
                    'is_fasum' => (bool) ($application->metadata['is_fasum'] ?? false),
                    'source' => 'pbb_nop_application',
                    'application_id' => $application->id,
                ],
            ]
        );

        // 4. Hitung NJOP & PBB terhutang dengan PbbCalculationService
        $pbbService = app(PbbCalculationService::class);
        $nir = (float) ($application->nir_applied ?? 500000);
        $buildingRate = (float) ($application->building_area > 0 ? 1200000 : 0);
        $calc = $pbbService->calculateAuto(
            (float) $application->land_area,
            $nir,
            (float) $application->building_area,
            $buildingRate
        );

        $isFasum = (bool) ($application->metadata['is_fasum'] ?? false);
        $pbbAmount = $isFasum ? 0 : max(25000, (float) ($calc['pbb_terhutang'] ?? 0));
        $currentYear = (string) date('Y');

        // 5. Simpan / Sinkronisasi ke SpptRecord
        SpptRecord::updateOrCreate(
            ['nop' => $nop, 'tax_year' => (int) $currentYear],
            [
                'taxpayer_name' => $application->name,
                'taxpayer_address' => $application->address,
                'object_address' => $application->address,
                'kelurahan' => $application->metadata['kelurahan'] ?? null,
                'kecamatan' => $application->metadata['kecamatan'] ?? null,
                'land_area_m2' => (float) $application->land_area,
                'building_area_m2' => (float) $application->building_area,
                'land_class' => $calc['kelas_bumi'] ?? '-',
                'building_class' => $calc['kelas_bangunan'] ?? '-',
                'land_njop_per_m2' => (float) ($calc['njop_bumi_per_m2'] ?? 0),
                'building_njop_per_m2' => (float) ($calc['njop_bangunan_per_m2'] ?? 0),
                'total_land_njop' => (float) ($calc['total_njop_bumi'] ?? 0),
                'total_building_njop' => (float) ($calc['total_njop_bangunan'] ?? 0),
                'njoptkp' => (float) ($calc['njoptkp'] ?? 10000000),
                'taxable_njop' => (float) ($calc['njop_kena_pajak'] ?? 0),
                'tax_rate' => (float) ($calc['tariff'] ?? 0.001),
                'tax_amount' => $pbbAmount,
                'payment_status' => $pbbAmount == 0 ? 'LUNAS' : 'BELUM LUNAS',
                'data_source' => $application->is_bpn_verified ? 'bpn_sync' : 'manual_input',
            ]
        );

        // 6. Terbitkan Tagihan (Bill) SPPT internal tahun berjalan bila belum ada dan pbbAmount > 0
        $existingBill = Bill::where('tax_object_id', $taxObject->id)
            ->where('period', $currentYear)
            ->first();

        if (! $existingBill && $pbbAmount > 0) {
            $billService = app(BillCreationService::class);
            $billService->createForTaxObject(
                $taxObject,
                null,
                $currentYear,
                [],
                [
                    'source' => 'pbb_auto_billing',
                    'application_id' => $application->id,
                    'nop' => $nop,
                    'calculated_pbb' => $calc,
                ],
                null,
                'pbb_auto_billing',
                $pbbAmount
            );
        }
    }
}

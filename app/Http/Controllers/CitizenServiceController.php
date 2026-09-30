<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\MarketStall;
use App\Models\PbbNopApplication;
use App\Models\RetributionClassification;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\TaxpayerPbbObject;
use App\Models\Verification;
use App\Services\AiSmartGisService;
use App\Services\BillingService;
use App\Services\CloudinaryService;
use App\Services\PbbBapendaService;
use App\Services\RequirementFileService;
use App\Services\TaxCalculationService;
use App\Services\WaGatewayService;
use App\Support\NopHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CitizenServiceController extends Controller
{
    /**
     * List all active classifications for the logged-in citizen
     */
    public function index(Request $request)
    {
        $taxpayer = $this->authenticatedTaxpayer($request);

        $classifications = RetributionClassification::active()
            ->whereHas('retributionType', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['retributionType.opd'])
            ->get()
            ->map(function ($cls) use ($taxpayer) {
                $objects = TaxObject::where('taxpayer_id', $taxpayer->id)
                    ->where('retribution_classification_id', $cls->id)
                    ->get();

                return [
                    'id' => $cls->id,
                    'name' => $cls->name,
                    'type_id' => $cls->retribution_type_id,
                    'type_name' => $cls->retributionType->name,
                    'category' => $cls->retributionType->category,
                    'icon' => $cls->icon ?: $cls->retributionType->icon,
                    'base_amount' => $cls->retributionType->base_amount,
                    'unit' => $cls->retributionType->unit,
                    'opd' => $cls->retributionType->opd,
                    'object_count' => $objects->count(),
                    'active_objects_count' => $objects->where('status', 'active')->count(),
                    'calculation_formula' => $cls->calculation_formula,
                ];
            });

        return response()->json(['data' => $classifications]);
    }

    /**
     * Get classification detail with list of objects and bills
     */
    public function show(Request $request, $id)
    {
        $taxpayer = $this->authenticatedTaxpayer($request);

        $classification = RetributionClassification::with(['retributionType.opd'])
            ->findOrFail($id);

        // Layanan dinonaktifkan sementara (contoh: 201 Kesehatan menunggu alur BLUD)
        if (array_key_exists('is_active', $classification->getAttributes()) && ! $classification->is_active) {
            return response()->json(['message' => 'Layanan ini untuk sementara tidak tersedia.'], 404);
        }

        $service = $classification->retributionType;

        $objects = TaxObject::where('taxpayer_id', $taxpayer->id)
            ->where('retribution_classification_id', $classification->id)
            ->with('zone')
            ->get();

        $activeObjectIds = $objects->where('status', 'active')->pluck('id');

        $bills = Bill::whereIn('tax_object_id', $activeObjectIds)
            ->with(['opd:id,name', 'taxObject'])
            ->latest()
            ->get();

        $cleanRequirements = is_array($classification->requirements)
            ? $classification->requirements
            : (json_decode($classification->requirements, true) ?: []);

        // Sanitasi blanko persampahan (DLH) — buang requirement usang kartu pelanggan/kontrak
        if ($classification->id == 198 || $classification->code === 'RET-SMP' || ($classification->opd_id == 4) || stripos($classification->name, 'sampah') !== false) {
            $cleanRequirements = collect($cleanRequirements)
                ->reject(fn ($r) => preg_match('/pelanggan|kontrak/i', $r['key'] ?? '') || preg_match('/pelanggan|kontrak/i', $r['name'] ?? '') || preg_match('/pelanggan|kontrak/i', $r['label'] ?? ''))
                ->values()
                ->toArray();
        }

        // Sanitasi Pajak Reklame — hapus requirement foto lokasi/titik pemasangan reklame
        if ($classification->id == 188 || ($classification->code ?? '') === 'REKLAME' || stripos($classification->name, 'reklame') !== false) {
            $cleanRequirements = collect($cleanRequirements)
                ->reject(fn ($r) => ($r['key'] ?? '') === 'foto_titik' || ($r['key'] ?? '') === 'foto_titik_reklame' || preg_match('/foto lokasi|lingkungan tempat pasang reklame/i', ($r['name'] ?? '').' '.($r['label'] ?? '')))
                ->values()
                ->toArray();
        }

        // ── Audit 20 Sep 2026: apakah layanan ini bisa dihitung warga? ──
        // Formula `tarif_flat` butuh nominal dari tabel retribution_rates.
        // Kalau belum ada tarif aktif, kalkulator akan selalu Rp 0 → jangan
        // tampilkan tombol kalkulator, dan beri tahu warga tarif ditetapkan petugas.
        $formula = trim((string) $classification->calculation_formula);
        $isFlatOnly = (bool) preg_match('/^\s*tarif_flat\s*$/i', $formula);

        $flatRates = collect();
        if ($isFlatOnly) {
            $flatRates = \DB::table('retribution_rates')
                ->where('retribution_classification_id', $classification->id)
                ->where('is_active', 1)
                ->orderBy('amount')
                ->get(['name', 'amount', 'unit'])
                ->map(fn ($r) => [
                    'name' => $r->name,
                    'amount' => (float) $r->amount,
                    'unit' => $r->unit,
                ])
                ->values();
        }

        // true  = warga boleh hitung sendiri (ada tarif, atau formula bukan tarif_flat)
        // false = tarif ditetapkan petugas, tombol kalkulator disembunyikan
        $hasFlatTariff = ! $isFlatOnly || $flatRates->isNotEmpty();

        return response()->json([
            'data' => [
                'id' => $classification->id,
                'code' => $classification->code,
                'name' => $classification->name,
                'type_id' => $service->id,
                'type_name' => $service->name,
                'category' => $service->category,
                'billing_cycle' => $service->billing_cycle ?? 'monthly',
                'icon' => $classification->icon ?: $service->icon,
                'base_amount' => $service->base_amount,
                'unit' => $service->unit,
                'opd' => $service->opd,
                'form_schema' => $classification->form_schema,
                'requirements' => $cleanRequirements,
                'calculation_formula' => $classification->calculation_formula,
                'has_flat_tariff' => $hasFlatTariff,
                'flat_tariff_options' => $flatRates,
                'objects' => $objects,
                'bills' => $bills,
            ],
        ]);
    }

    /**
     * Register a new tax object for a classification
     */
    public function register(Request $request, $id)
    {
        $taxpayer = $this->authenticatedTaxpayer($request);
        $classification = RetributionClassification::with('retributionType')->findOrFail($id);

        // Layanan dinonaktifkan sementara — tolak pendaftaran objek baru
        if (array_key_exists('is_active', $classification->getAttributes()) && ! $classification->is_active) {
            return response()->json(['message' => 'Layanan ini untuk sementara tidak tersedia.'], 404);
        }

        $service = $classification->retributionType;

        // Ekstrak NIB dari metadata jika tidak dikirim sebagai parameter top-level
        if (! $request->has('nib') || empty($request->input('nib'))) {
            $rawMeta = $request->input('metadata');
            if (is_string($rawMeta)) {
                $decoded = json_decode($rawMeta, true) ?: [];
                $foundNib = $decoded['nib'] ?? $decoded['nib_usaha'] ?? null;
                if (! empty($foundNib) && is_string($foundNib)) {
                    $request->merge(['nib' => $foundNib]);
                }
            } elseif (is_array($rawMeta)) {
                $foundNib = $rawMeta['nib'] ?? $rawMeta['nib_usaha'] ?? null;
                if (! empty($foundNib) && is_string($foundNib)) {
                    $request->merge(['nib' => $foundNib]);
                }
            }
        }

        // GATE NIB (PTSP-13): layanan usaha wajib NIB
        NibPublicController::enforceNibGate($request, $classification, $taxpayer);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'zone_id' => 'nullable|exists:zones,id',
            'metadata' => 'nullable',
        ]);

        $cloudinary = app(CloudinaryService::class);
        $metadata = $request->input('metadata', []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Smart Demarcation Disperindag Pasar (Perda Kota Baubau No. 1 Tahun 2024):
        // Warga dapat mendaftar dari pintu "Pasar". Sistem secara otomatis memetakan klasifikasi & rekening PAD:
        // - Jika lapak bertipe KIOS -> otomatis dialihkan ke ID 203 (JU-TKU / Retribusi Jasa Usaha Sewa Kios Tahunan)
        // - Jika lapak bertipe LOS / Pelataran -> otomatis dialihkan ke ID 202 (RET-PSR / Retribusi Jasa Umum Bulanan/Harian)
        if (in_array((int) $classification->id, [200, 202, 203])) {
            $buildingName = $metadata['nama_gedung'] ?? null;
            $facilityType = strtolower((string) ($metadata['jenis_fasilitas'] ?? ''));

            if ($buildingName && empty($facilityType)) {
                $sampleStall = MarketStall::where('building_name', $buildingName)->first();
                if ($sampleStall) {
                    $facilityType = strtolower((string) $sampleStall->stall_type);
                    $metadata['jenis_fasilitas'] = $facilityType;
                }
            }

            if ($facilityType === 'kios' && (int) $classification->id !== 203) {
                $targetClass = RetributionClassification::with('retributionType')->find(203);
                if ($targetClass) {
                    $classification = $targetClass;
                    $service = $classification->retributionType;
                }
            } elseif (in_array($facilityType, ['los', 'pelataran']) && (int) $classification->id !== 202) {
                $targetClass = RetributionClassification::with('retributionType')->find(202);
                if ($targetClass) {
                    $classification = $targetClass;
                    $service = $classification->retributionType;
                }
            }
        }

        // Upload field bertipe 'upload'/'file' dari form_schema (foto_lokasi, foto_usaha, KTP, dll)
        // Fix 28 Agu 2026: sebelumnya field ini dirender textbox & tidak pernah diproses sebagai file.
        foreach ($classification->form_schema ?? [] as $schemaField) {
            $fkey = $schemaField['key'] ?? null;
            $ftype = $schemaField['type'] ?? null;
            if (! $fkey || ! in_array($ftype, ['upload', 'file'], true)) {
                continue;
            }
            if ($request->hasFile($fkey)) {
                $request->validate([
                    $fkey => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
                ]);
                $metadata[$fkey] = $cloudinary->upload(
                    $request->file($fkey),
                    'citizen/documents/'.$service->id
                );
            }
        }

        // Auto-calculate field readonly / turunan jika input sumbernya tersedia
        if (empty($metadata['luas']) && ! empty($metadata['panjang']) && ! empty($metadata['lebar'])) {
            $p = (float) $metadata['panjang'];
            $l = (float) $metadata['lebar'];
            $s = (float) ($metadata['sisi'] ?? 1);
            if ($p > 0 && $l > 0) {
                $metadata['luas'] = round($p * $l * $s, 2);
            }
        }

        // Normalisasi Reklame Multi-Titik (Pajak Reklame ID 188 / REKLAME)
        if (!empty($metadata['titik_lokasi'])) {
            if (is_string($metadata['titik_lokasi'])) {
                $metadata['titik_lokasi'] = json_decode($metadata['titik_lokasi'], true) ?: [];
            }
            if (is_array($metadata['titik_lokasi']) && count($metadata['titik_lokasi']) > 0) {
                $metadata['jumlah_titik'] = count($metadata['titik_lokasi']);
                $metadata['unit'] = $metadata['jumlah_titik'];

                // Auto-fill koordinat dan alamat dari titik pertama jika belum diisi
                $firstPoint = $metadata['titik_lokasi'][0] ?? null;
                if ($firstPoint && is_array($firstPoint)) {
                    if (empty($metadata['koordinat_usaha']) && !empty($firstPoint['latitude']) && !empty($firstPoint['longitude'])) {
                        $metadata['koordinat_usaha'] = $firstPoint['latitude'].','.$firstPoint['longitude'];
                    }
                    if (empty($metadata['alamat_usaha']) && !empty($firstPoint['alamat'])) {
                        $metadata['alamat_usaha'] = $firstPoint['alamat'];
                    }
                }
            }
        }
        if (empty($metadata['penjualan']) && ! empty($metadata['volume']) && ! empty($metadata['harga'])) {
            $v = (float) $metadata['volume'];
            $h = (float) $metadata['harga'];
            if ($v > 0 && $h > 0) {
                $metadata['penjualan'] = round($v * $h);
            }
        }
        if (empty($metadata['hda']) && ! empty($metadata['tujuan_pengambilan'])) {
            $hdaTarifMap = [
                'niaga_kecil' => 2500,
                'niaga_besar' => 4000,
                'industri' => 5000,
                'wisata' => 6000,
            ];
            $metadata['hda'] = $hdaTarifMap[$metadata['tujuan_pengambilan']] ?? 2500;
        }

        // Auto-set Harga Patokan MBLB resmi Perda 1/2024 jika belum diisi
        if (isset($metadata['jenis_mineral']) && empty($metadata['harga'])) {
            $mblbTarifMap = [
                'gamping' => 35000,
                'pasir' => 50000,
                'tanah_urug' => 25000,
                'tanah_liat' => 30000,
                'sirtu' => 45000,
                'andesit' => 60000,
            ];
            $metadata['harga'] = $mblbTarifMap[$metadata['jenis_mineral']] ?? 35000;
        }

        // Sinkronisasi alias omset <-> omzet dan fallback 0 untuk registrasi profil awal objek
        if (! isset($metadata['omzet']) && isset($metadata['omset'])) {
            $metadata['omzet'] = $metadata['omset'];
        }
        if (! isset($metadata['omset']) && isset($metadata['omzet'])) {
            $metadata['omset'] = $metadata['omzet'];
        }
        if (! isset($metadata['omset']) && ! isset($metadata['omzet'])) {
            $metadata['omset'] = 0;
            $metadata['omzet'] = 0;
        }

        // Auto-sinkronkan field redundan (nama_usaha, alamat_usaha, koordinat_usaha, legal_consent) dari payload pendaftaran utama
        if (empty($metadata['nama_usaha']) && $request->filled('name')) {
            $metadata['nama_usaha'] = $request->input('name');
        }
        if (empty($metadata['alamat_usaha']) && $request->filled('address')) {
            $metadata['alamat_usaha'] = $request->input('address');
        }
        if (empty($metadata['koordinat_usaha']) && $request->filled('latitude') && $request->filled('longitude')) {
            $metadata['koordinat_usaha'] = $request->input('latitude').','.$request->input('longitude');
        }
        // Pencatatan Yuridis Pakta Integritas / Pernyataan Hukum
        $metadata['legal_consent'] = true;
        $metadata['legal_consent_audit'] = [
            'agreed' => true,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];

        // Auto-kalkulasi parameter teknis PBJT Tenaga Listrik Non-PLN (Pembangkit Mandiri)
        if ($classification->id == 196 || ($classification->code ?? '') === 'PBJT-NON-PLN') {
            $kva = (float) ($metadata['kapasitas_daya_kva'] ?? 0);
            $jamPerHari = (float) ($metadata['jam_operasional_per_hari'] ?? 8);
            if ($jamPerHari <= 0) {
                $jamPerHari = 8;
            }
            $metadata['jam_nyala_sebulan'] = $jamPerHari * 30;
            // Standar estimasi kWh = kVA * cos phi (0.8) * jam nyala sebulan
            $metadata['estimasi_kwh_bulanan'] = round($kva * 0.8 * $metadata['jam_nyala_sebulan'], 2);
            $metadata['harga_satuan_njtl'] = 1444; // Standar NJTL tarif acuan listrik
        }

        // Auto-isi "Link Lokasi Google Maps" bila kosong: turunkan dari koordinat
        // jika tersedia, atau dari alamat objek agar warga tak wajib klik peta.
        if (empty($metadata['lokasi_google_maps'])) {
            $coord = $request->input('latitude').','.$request->input('longitude');
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $coord = round((float) $request->input('latitude'), 6).','.round((float) $request->input('longitude'), 6);
                $metadata['lokasi_google_maps'] = 'https://maps.google.com/?q='.$coord;
            } else {
                foreach (['koordinat_usaha', 'koordinat', 'titik_koordinat'] as $metaKey) {
                    $raw = $metadata[$metaKey] ?? null;
                    if (is_string($raw) && preg_match('/^[-+]?\d+(\.\d+)?\s*[,;\s]\s*[-+]?\d+(\.\d+)?$/', trim($raw))) {
                        $parts = preg_split('/[,;\s]+/', trim($raw));
                        $metadata['lokasi_google_maps'] = 'https://maps.google.com/?q='.round((float) $parts[0], 6).','.round((float) $parts[1], 6);
                        break;
                    }
                }
            }
            if (empty($metadata['lokasi_google_maps']) && ! empty($metadata['alamat_usaha'])) {
                $metadata['lokasi_google_maps'] = 'https://www.google.com/maps/search/?api=1&query='.urlencode($metadata['alamat_usaha']);
            } elseif (empty($metadata['lokasi_google_maps']) && $request->filled('address')) {
                $metadata['lokasi_google_maps'] = 'https://www.google.com/maps/search/?api=1&query='.urlencode((string) $request->input('address'));
            }
        }

        $metadata = $this->applySchemaDefaults($classification->form_schema ?? [], $metadata);
        $missingMetadata = $this->missingRequiredMetadata($classification->form_schema ?? [], $metadata);
        if (! empty($missingMetadata)) {
            throw ValidationException::withMessages($missingMetadata);
        }

        $isPbb = (stripos($classification->name ?? '', 'pbb') !== false) || ($classification->id == 186);
        $nop = $metadata['nop_pbb'] ?? null;
        $h2hVerified = false;
        if ($isPbb && ! empty($nop)) {
            if (TaxObject::where('metadata->nop_pbb', $nop)->exists()) {
                throw ValidationException::withMessages([
                    'metadata' => ['NOP PBB sudah terdaftar. Silakan gunakan NOP lain atau hubungi Bapenda.'],
                ]);
            }

            if (PbbNopApplication::where('nop', $nop)->whereIn('status', ['PENDING', 'SURVEY', 'APPROVED'])->exists()) {
                throw ValidationException::withMessages([
                    'metadata' => ['NOP PBB sedang dalam proses pendaftaran.'],
                ]);
            }

            // Zero-Trust Server Verification (Task 1951): Inquiry langsung dari server
            $h2hVerified = false;
            try {
                $inquiry = app(PbbBapendaService::class)->inquiryWithFallback($nop, date('Y'));
                if (($inquiry['status'] ?? 0) === 200) {
                    if (! empty($inquiry['nama_wp'])) {
                        $metadata['nama_wp'] = $inquiry['nama_wp'];
                    }
                    if (! empty($inquiry['alamat_wp'])) {
                        $metadata['alamat_wp'] = $inquiry['alamat_wp'];
                    }
                    if (! empty($inquiry['kelurahan'])) {
                        $metadata['kelurahan'] = $inquiry['kelurahan'];
                    }
                    if (! empty($inquiry['kota'])) {
                        $metadata['kota'] = $inquiry['kota'];
                    }
                    $metadata['pbb_pokok'] = (float) ($inquiry['pbb_pokok'] ?? $metadata['pbb_pokok'] ?? 0);
                    $metadata['njop_total'] = (float) ($inquiry['njop_total'] ?? $metadata['njop_total'] ?? 0);
                    $metadata['luas_bumi'] = (float) ($inquiry['luas_bumi'] ?? $metadata['luas_bumi'] ?? 0);
                    $metadata['luas_bangunan'] = (float) ($inquiry['luas_bangunan'] ?? $metadata['luas_bangunan'] ?? 0);
                    $metadata['data_source'] = $inquiry['source'] ?? 'simpbb';
                    $metadata['h2h_verified_at'] = now()->toISOString();
                    // Auto-approve: NOP sudah terverifikasi di master Bapenda (inquiry H2H 200 OK).
                    // Verifikasi admin tidak menambah nilai lagi -> langsung aktif, warga bisa bayar.
                    $h2hVerified = true;
                }
            } catch (\Exception $e) {
                Log::warning('Zero-trust PBB inquiry lookup skipped/failed: '.$e->getMessage());
            }
        }

        // Handle dynamic document uploads based on requirements from this classification
        $requirements = $classification->requirements ?? [];
        if ($classification->id == 188 || ($classification->code ?? '') === 'REKLAME' || stripos($classification->name, 'reklame') !== false) {
            $requirements = collect($requirements)
                ->reject(fn ($r) => ($r['key'] ?? '') === 'foto_titik' || ($r['key'] ?? '') === 'foto_titik_reklame' || preg_match('/foto lokasi|lingkungan tempat pasang reklame/i', ($r['name'] ?? '').' '.($r['label'] ?? '')))
                ->values()
                ->toArray();
        }
        $missingFiles = $this->missingRequiredFiles($request, $requirements);
        if (! empty($missingFiles)) {
            throw ValidationException::withMessages($missingFiles);
        }

        $processedKeys = [];
        $requirementFiles = app(RequirementFileService::class);

        foreach ($requirements as $index => $req) {
            $key = $req['key'] ?? null;
            if ($key && $request->hasFile($key)) {
                $request->validate([$key => $requirementFiles->rulesFor($req, $index)]);
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key),
                    'citizen/documents/'.$service->id
                );
                $processedKeys[] = $key;
            }
        }

        // Safety fallback: Handle common keys from mobile app if they were sent but not in requirements
        $fallbacks = ['foto_lokasi_open_kamera', 'formulir_data_dukung'];
        foreach ($fallbacks as $key) {
            if (! in_array($key, $processedKeys) && $request->hasFile($key)) {
                $request->validate([
                    $key => $requirementFiles->rulesFor([
                        'key' => $key,
                        'type' => $key === 'foto_lokasi_open_kamera' ? 'image' : 'document',
                    ]),
                ]);
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key),
                    'citizen/documents/'.$service->id
                );
            }
        }

        // AI Inspection & SmartGIS Ground Truth Data Synthesis
        $firstUploadedFile = null;
        foreach ($requirements as $req) {
            $k = $req['key'] ?? null;
            if ($k && $request->hasFile($k)) {
                $firstUploadedFile = $request->file($k);
                break;
            }
        }
        if (! $firstUploadedFile) {
            foreach (['foto_lokasi_open_kamera', 'formulir_data_dukung', 'foto_titik', 'foto_lokasi'] as $fbKey) {
                if ($request->hasFile($fbKey)) {
                    $firstUploadedFile = $request->file($fbKey);
                    break;
                }
            }
        }

        $declaredLat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $declaredLng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $aiService = app(AiSmartGisService::class);
        $photoAudit = $aiService->inspectUploadedPhoto($firstUploadedFile, $declaredLat, $declaredLng);
        $smartGis = $aiService->querySmartGisGroundTruth($declaredLat, $declaredLng, $classification->code ?? 'MBLB');
        $aiAnalysis = $aiService->synthesizeValidation($photoAudit, $smartGis, $classification->name ?? 'Layanan');

        $metadata['ai_smartgis_analysis'] = $aiAnalysis;
        $metadata['sla_hours'] = 24;
        $metadata['sla_deadline'] = Carbon::now()->addHours(24)->toIso8601String();
        $metadata['sla_label'] = 'Maksimal 1 x 24 Jam Kerja';
        $metadata['estimated_completion_at'] = Carbon::now()->addHours(24)->translatedFormat('d F Y, H:i').' WITA';

        // Auto-approve untuk PBB: NOP 18 digit + form lengkap = langsung aktif.
        // H2H Bapenda tidak selalu tersedia (kredensial/integrasi belum siap), sehingga
        // bergantung pada h2h_verified_at membuat SEMUA pendaftaran nyangkut di PENDING
        // dan tidak pernah muncul di admin. Zero-trust H2H tetap dijalankan bila tersedia.
        $nopFormatValid = (bool) preg_match('/^[0-9]{18}$/', (string) $nop);
        $autoApproved = $isPbb && ($h2hVerified || $nopFormatValid);

        [$taxObject, $verification] = DB::transaction(function () use ($request, $taxpayer, $classification, $service, $metadata, $isPbb, $autoApproved, $photoAudit) {
            // Create the tax object
            // Lengkapi kolom tabel dari metadata — halaman verifikasi admin Bapenda
            // membaca kolom tabel, bukan metadata. Sebelum ini luas/kelurahan/
            // penanggung jawab KOSONG walau datanya ada di hasil inquiry H2H,
            // sehingga verifikator tidak bisa menilai kewajaran objek.
            $pick = function (array $keys, $default = null) use ($metadata, $request) {
                foreach ($keys as $k) {
                    if (! empty($metadata[$k])) {
                        return $metadata[$k];
                    }
                }
                foreach ($keys as $k) {
                    $v = $request->input($k);
                    if (! empty($v)) {
                        return $v;
                    }
                }

                return $default;
            };

            $num = function ($v) {
                if ($v === null || $v === '') {
                    return null;
                }

                return is_numeric($v) ? (float) $v : (float) preg_replace('/[^0-9.]/', '', (string) $v);
            };

            $taxObject = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $service->id,
                'retribution_classification_id' => $classification->id,
                'opd_id' => $service->opd_id,
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'address' => $request->address ?: ($metadata['titik_lokasi'][0]['alamat'] ?? 'Kota Baubau'),
                'latitude' => $request->latitude ?? ($metadata['titik_lokasi'][0]['latitude'] ?? null),
                'longitude' => $request->longitude ?? ($metadata['titik_lokasi'][0]['longitude'] ?? null),
                'metadata' => $metadata,
                'status' => $autoApproved ? 'active' : 'pending',

                // --- Kelengkapan untuk verifikasi admin Bapenda ---
                'land_size_m2' => $num($pick(['luas_bumi', 'land_size_m2', 'luas_tanah'])),
                'building_size_m2' => $num($pick(['luas_bangunan', 'building_size_m2'])),
                'sub_district' => $pick(['kelurahan', 'sub_district']),
                'district' => $pick(['kecamatan', 'kota', 'district']),
                'penanggung_jawab_nama' => $pick(['nama_wp', 'penanggung_jawab_nama'], $request->name),
                'penanggung_jawab_kontak' => $pick(['kontak_wp', 'no_hp', 'telepon', 'penanggung_jawab_kontak']),
                'nama_penandatangan' => $pick(['nama_penandatangan'], $request->name),
                'tanggal_pernyataan' => $pick(['tanggal_pernyataan']) ?: now()->toDateString(),
                'nomor_formulir' => $pick(['nomor_formulir']) ?: ('SPOP-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)),
            ]);

            // Auto-link ke unit lapak MarketStall jika terdapat kecocokan data pasar Disperindag
            $stallNumber = $metadata['nomor_kios'] ?? $metadata['nomor_unit'] ?? null;
            $marketBuilding = $metadata['nama_gedung'] ?? null;
            if ($stallNumber && $marketBuilding && class_exists(MarketStall::class)) {
                $matchedStall = MarketStall::where('building_name', $marketBuilding)
                    ->where('stall_number', (string) $stallNumber)
                    ->first();
                if ($matchedStall) {
                    $matchedStall->update([
                        'tax_object_id' => $taxObject->id,
                        'taxpayer_id' => $taxpayer->id,
                        'merchant_name' => $taxpayer->name,
                        'status' => 'aktif',
                    ]);
                    $metadata['market_stall_id'] = $matchedStall->id;
                    $taxObject->update(['metadata' => $metadata]);
                }
            }

            // Pick the best available file URL (prioritize real photo/image, exclude maps coordinates)
            $firstFileUrl = $this->resolvePrimaryProofUrl($metadata);

            // Calculate initial estimated amount for verification record
            $estimatedAmount = 0;
            try {
                $calcService = app(TaxCalculationService::class);
                $estimatedAmount = (float) $calcService->calculateAmount($taxObject);
            } catch (\Throwable $e) {
                \Log::warning('Failed calculating estimated amount for verification: '.$e->getMessage());
            }

            // Create a verification record for the admin to review
            $verification = Verification::create([
                'opd_id' => $service->opd_id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObject->id,
                'document_number' => 'REG-'.strtoupper(uniqid()),
                'taxpayer_name' => $taxpayer->name,
                'type' => 'Pendaftaran Objek',
                'amount' => $estimatedAmount,
                'status' => $autoApproved ? 'approved' : 'pending',
                'proof_file_url' => $firstFileUrl,
                'submitted_at' => Carbon::now(),
                'verified_at' => $autoApproved ? Carbon::now() : null,
                'notes' => $autoApproved
                    ? 'NOP terverifikasi otomatis via inquiry Bapenda (H2H). Objek langsung aktif.'
                    : 'Pendaftaran unit baru ('.$classification->name.'): '.$taxObject->name.(
                        (($photoAudit['status'] ?? '') === 'UNVERIFIED_OR_RANDOM')
                            ? ' [AUDIT AI: Dokumen foto tidak memiliki geotag kamera lapangan (terindikasi foto galeri/sembarang). Didekatkan dengan data riel SmartGIS. Disarankan verifikasi faktual DLH/PUPR/PERKIMTAN].'
                            : ''
                    ),
            ]);

            if (! $taxpayer->opd_id) {
                $taxpayer->update(['opd_id' => $service->opd_id]);
            }

            // PBB: auto-link ke TaxpayerPbbObject supaya objek langsung muncul di "Objek Saya" (pbb-tagihan)
            // Satu sumber objek PBB warga: daftar via layanan = otomatis bisa cek & bayar.
            if ($isPbb) {
                $nopLink = $metadata['nop_pbb'] ?? null;
                if (! empty($nopLink)) {
                    TaxpayerPbbObject::firstOrCreate(
                        ['taxpayer_id' => $taxpayer->id, 'nop' => $nopLink],
                        [
                            'name_on_sppt' => $metadata['nama_wp'] ?? $taxpayer->name ?? null,
                            'address_on_sppt' => $metadata['alamat_wp'] ?? $request->address ?? null,
                            'kelurahan' => $metadata['kelurahan'] ?? $request->sub_district ?? null,
                            'kota' => $metadata['kota'] ?? $request->district ?? null,
                            'is_verified' => true,
                            'notes' => 'Otomatis dari pendaftaran layanan PBB (REG-'.strtoupper(uniqid()).')',
                        ]
                    );

                    // NOP sudah terverifikasi di master Bapenda -> tidak masuk antrean verifikasi admin.
                    PbbNopApplication::updateOrCreate(
                        ['nop' => $nopLink],
                        [
                            'user_id' => $taxpayer->user_id ?? null,
                            'nik' => $taxpayer->nik ?? null,
                            'name' => $metadata['nama_wp'] ?? $taxpayer->name ?? null,
                            'address' => $metadata['alamat_wp'] ?? $request->address ?? null,
                            'status' => $autoApproved ? 'APPROVED' : 'PENDING',
                            'registered_for' => 'self',
                            // Kolom NOT NULL tanpa default di tabel pbb_nop_applications
                            // wajib diisi eksplisit, kalau tidak: SQLSTATE 1364.
                            'land_area' => (float) ($metadata['luas_bumi'] ?? 0),
                            'building_area' => (float) ($metadata['luas_bangunan'] ?? 0),
                            'ktp_file_path' => $firstFileUrl ?: 'pending',
                            'akte_file_path' => $firstFileUrl ?: 'pending',
                            'metadata' => [
                                'auto_approved' => $autoApproved,
                                'source' => 'layanan_pbb',
                                'h2h_verified_at' => $metadata['h2h_verified_at'] ?? null,
                            ],
                        ]
                    );
                }
            }

            // ── Integrasi DPMPTSP (PTSP): Opsi NIB Sarang Burung Walet & Notifikasi Perizinan ──
            $isWalet = ((int) $classification->id === 190) || (strtoupper((string) ($classification->code ?? '')) === 'WALET') || stripos((string) ($classification->name ?? ''), 'walet') !== false;
            $rawNib = $metadata['nib'] ?? $request->input('nib') ?? $metadata['nib_usaha'] ?? null;
            $nib = ! empty($rawNib) ? preg_replace('/[^0-9]/', '', (string) $rawNib) : null;
            $requestPtspAssistance = ! empty($metadata['ptsp_request_assistance']);

            if (! empty($nib) && strlen($nib) >= 13 && strlen($nib) <= 16) {
                // Tautkan NIB ke profil taxpayer bila belum ada
                if (empty($taxpayer->nib)) {
                    $taxpayer->update(['nib' => $nib]);
                }

                // Perbarui metadata objek pajak dengan status keterhubungan PTSP
                $metadata['nib'] = $nib;
                $metadata['has_nib'] = true;
                $metadata['ptsp_notified'] = true;
                $metadata['ptsp_notified_at'] = Carbon::now()->toIso8601String();
                $metadata['ptsp_channel'] = 'dpmptsp_system';
                $metadata['ptsp_status'] = 'terintegrasi';
                $taxObject->update(['metadata' => $metadata]);

                // Sinkronkan ke nib_nop_mappings
                if (Schema::hasTable('nib_nop_mappings')) {
                    $existsMapping = DB::table('nib_nop_mappings')->where('nib', $nib)->exists();
                    if (! $existsMapping) {
                        DB::table('nib_nop_mappings')->insert([
                            'nib' => $nib,
                            'nop' => $metadata['nop_pbb'] ?? null,
                            'taxpayer_name' => $taxpayer->name,
                            'land_area_m2' => ! empty($metadata['luas_bangunan']) ? (float) $metadata['luas_bangunan'] : null,
                            'building_area_m2' => ! empty($metadata['luas_bangunan']) ? (float) $metadata['luas_bangunan'] : null,
                            'match_status' => 'matched',
                            'source' => 'manual',
                            'last_synced_at' => Carbon::now(),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }
                }

                // Sinkronkan ke ptsp_data_candidates (Daftar Calon Wajib Pajak / Pelaku Usaha Ber-NIB DPMPTSP)
                if (Schema::hasTable('ptsp_data_candidates')) {
                    DB::table('ptsp_data_candidates')->updateOrInsert(
                        [
                            'nik' => $taxpayer->nik,
                            'jenis_usaha' => $isWalet ? 'Pajak Sarang Burung Walet' : ($classification->name ?? 'Usaha'),
                        ],
                        [
                            'nama' => $taxpayer->name,
                            'nik' => $taxpayer->nik,
                            'alamat_usaha' => $request->address ?: $taxpayer->address,
                            'jenis_usaha' => $isWalet ? 'Pajak Sarang Burung Walet' : ($classification->name ?? 'Usaha'),
                            'no_hp' => $taxpayer->phone ?: '-',
                            'status' => 'nib_terbit',
                            'nib' => $nib,
                            'keterangan' => 'Notifikasi otomatis dari pendaftaran Objek Pajak '.($isWalet ? 'Sarang Burung Walet' : $classification->name).": {$taxObject->name} (ID Objek: {$taxObject->id})",
                            'updated_at' => Carbon::now(),
                            'created_at' => Carbon::now(),
                        ]
                    );
                }

                // Kirim notifikasi resmi sistem DPMPTSP ke notification_logs
                if (Schema::hasTable('notification_logs')) {
                    DB::table('notification_logs')->insert([
                        'taxpayer_id' => $taxpayer->id,
                        'phone' => $taxpayer->phone ?: null,
                        'channel' => 'ptsp_system',
                        'type' => $isWalet ? 'ptsp_walet_nib_notification' : 'ptsp_nib_notification',
                        'reference_type' => 'tax_objects',
                        'reference_id' => $taxObject->id,
                        'message_preview' => 'Notifikasi DPMPTSP: Pendaftaran Objek Pajak '.($isWalet ? 'Sarang Burung Walet' : $classification->name)." '{$taxObject->name}' oleh {$taxpayer->name} dengan NIB {$nib} berhasil dicatat dan disinkronisasikan ke sistem perizinan PTSP.",
                        'status' => 'sent',
                        'sent_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            } elseif ($isWalet && $requestPtspAssistance) {
                // Wajib Pajak walet belum punya NIB dan meminta permohonan pendampingan PTSP
                if (Schema::hasTable('ptsp_data_candidates')) {
                    DB::table('ptsp_data_candidates')->updateOrInsert(
                        [
                            'nik' => $taxpayer->nik,
                            'jenis_usaha' => 'Pajak Sarang Burung Walet',
                        ],
                        [
                            'nama' => $taxpayer->name,
                            'nik' => $taxpayer->nik,
                            'alamat_usaha' => $request->address ?: $taxpayer->address,
                            'jenis_usaha' => 'Pajak Sarang Burung Walet',
                            'no_hp' => $taxpayer->phone ?: '-',
                            'status' => 'menunggu',
                            'nib' => null,
                            'keterangan' => "Permohonan pendampingan penerbitan NIB dari pendaftaran Sarang Burung Walet: {$taxObject->name} (ID Objek: {$taxObject->id})",
                            'updated_at' => Carbon::now(),
                            'created_at' => Carbon::now(),
                        ]
                    );
                }

                if (Schema::hasTable('notification_logs')) {
                    DB::table('notification_logs')->insert([
                        'taxpayer_id' => $taxpayer->id,
                        'phone' => $taxpayer->phone ?: null,
                        'channel' => 'ptsp_system',
                        'type' => 'ptsp_assistance_request',
                        'reference_type' => 'tax_objects',
                        'reference_id' => $taxObject->id,
                        'message_preview' => "Permohonan Pendampingan DPMPTSP: Pelaku usaha Sarang Burung Walet '{$taxObject->name}' ({$taxpayer->name}) mengajukan bantuan pendampingan pembuatan NIB OSS.",
                        'status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                $metadata['ptsp_assistance_requested'] = true;
                $metadata['ptsp_status'] = 'menunggu_pendampingan';
                $taxObject->update(['metadata' => $metadata]);
            }

            return [$taxObject, $verification];
        });

        // Kirim notifikasi WhatsApp resmi ke nomor pemesan / terdaftar wajib pajak
        $phone = $request->input('phone')
            ?: $request->input('no_hp')
            ?: $request->input('telepon')
            ?: $request->input('kontak_pemohon')
            ?: ($metadata['kontak_pemohon'] ?? null)
            ?: ($metadata['no_hp'] ?? null)
            ?: ($metadata['telepon'] ?? null)
            ?: ($metadata['phone'] ?? null)
            ?: ($metadata['whatsapp'] ?? null)
            ?: ($metadata['kontak_wp'] ?? null)
            ?: $taxpayer->phone
            ?: ($request->user()?->phone ?? null);

        if (! empty($phone)) {
            // Update nomor HP taxpayer jika sebelumnya belum terisi
            if (empty($taxpayer->phone)) {
                try {
                    $taxpayer->update(['phone' => $phone]);
                } catch (\Throwable $ignored) {}
            }

            try {
                $waGateway = app(WaGatewayService::class);
                $docNumber = $verification->document_number;
                $objName = $taxObject->name;
                $serviceName = $classification->name ?: $service->name;
                $address = $taxObject->address ?: 'Kota Baubau';
                $slaDeadline = Carbon::now()->addHours(24)->translatedFormat('d M Y, H:i');

                $waMessage = "🏛️ *PEMERINTAH KOTA BAUBAU*\n"
                           ."*Badan Pendapatan Daerah (BAPENDA)*\n"
                           ."━━━━━━━━━━━━━━━━━━━━━━━━\n"
                           ."Halo, *{$taxpayer->name}*!\n\n"
                           ."Pendaftaran unit/objek baru Anda telah berhasil kami terima:\n"
                           ."📋 *Layanan*: {$serviceName}\n"
                           ."🏷️ *Nama Objek*: {$objName}\n"
                           ."📍 *Lokasi*: {$address}\n"
                           ."🔢 *No. Registrasi*: `{$docNumber}`\n"
                           ."⏱️ *Estimasi Verifikasi*: Maksimal 1 x 24 Jam Kerja (Estimasi Selesai: {$slaDeadline} WITA)\n\n"
                           ."Status permohonan Anda saat ini: *MENUNGGU VERIFIKASI*\n"
                           ."Data lokasi Anda telah ditautkan dengan peta spasial SmartGIS Bapenda. Admin dan dinas teknis (DLH / PUPR / PERKIMTAN) sesuai Tupoksinya akan memverifikasi berkas dan keabsahan kegiatan usaha/galian.\n\n"
                           ."Anda dapat memantau status secara berkala melalui aplikasi *M-PAD Baubau*.\n\n"
                           .'_Pesan otomatis resmi Sistem M-PAD Pemerintah Kota Baubau_';

                $waGateway->sendMessage($phone, $waMessage);

                if (Schema::hasTable('notification_logs')) {
                    DB::table('notification_logs')->insert([
                        'taxpayer_id' => $taxpayer->id,
                        'phone' => $phone,
                        'channel' => 'whatsapp',
                        'type' => 'unit_registration_received',
                        'reference_type' => 'tax_objects',
                        'reference_id' => $taxObject->id,
                        'message_preview' => "Pendaftaran unit {$objName} diterima. No: {$docNumber}, SLA: 24 Jam.",
                        'status' => 'sent',
                        'sent_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp registration notification failed: '.$e->getMessage());
            }
        }

        return response()->json([
            'message' => $autoApproved
                ? 'NOP berhasil terverifikasi. Objek langsung aktif dan siap dibayar.'
                : 'Pendaftaran unit berhasil dikirim dan menunggu verifikasi',
            'data' => [
                'object' => $taxObject,
                'verification_id' => $verification->id,
                'auto_approved' => $autoApproved,
            ],
        ], 201);
    }

    private function applySchemaDefaults(array $schema, array $metadata): array
    {
        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if (! $key || array_key_exists($key, $metadata)) {
                continue;
            }

            if (array_key_exists('default_value', $field)) {
                $metadata[$key] = $field['default_value'];
            }
        }

        return $metadata;
    }

    private function missingRequiredMetadata(array $schema, array $metadata): array
    {
        $errors = [];

        foreach ($schema as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }

            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }

            // Field readonly seperti HDA, luas reklame, penjualan walet tidak diisi manual oleh warga
            if (! empty($field['readonly'])) {
                continue;
            }

            // Field pelaporan masa panen (volume / omzet panen) adalah bagian dari SPTPD, bukan pendaftaran master gedung objek walet
            if (in_array($key, ['volume', 'perkiraan_hasil_panen', 'hasil_panen', 'penjualan'], true)) {
                continue;
            }

            // Pengecualian diameter_pipa & kapasitas_pompa (hanya wajib jika meter rusak / tanpa meter)
            if (in_array($key, ['diameter_pipa', 'kapasitas_pompa'], true)) {
                $meterStatus = $metadata['status_meter'] ?? null;
                if ($meterStatus !== 'tanpa_meter' && $meterStatus !== 'rusak') {
                    continue;
                }
            }

            $value = $metadata[$key] ?? null;
            $missing = $value === null || (is_string($value) && trim($value) === '');

            if ($missing) {
                $label = $field['label'] ?? $key;
                $errors["metadata.{$key}"] = "{$label} wajib diisi.";
            }
        }

        return $errors;
    }

    private function missingRequiredFiles(Request $request, array $requirements): array
    {
        $errors = [];

        foreach ($requirements as $requirement) {
            if (! ($requirement['required'] ?? false)) {
                continue;
            }

            $key = $requirement['key'] ?? null;
            if (! $key || $request->hasFile($key)) {
                continue;
            }

            // Identitas/KTP terpusat dari akun warga terdaftar (tidak perlu upload ulang)
            if (preg_match('/(ktp|identitas|nik)/i', (string) $key) || preg_match('/(ktp|identitas)/i', (string) ($requirement['name'] ?? $requirement['label'] ?? ''))) {
                if ($request->user()) {
                    continue;
                }
            }

            // Bukti hak lahan bersifat opsional bagi pendaftaran objek walet
            if ($key === 'bukti_lahan') {
                continue;
            }

            $label = $requirement['label'] ?? $requirement['name'] ?? $key;
            $errors[$key] = "{$label} wajib diunggah.";
        }

        return $errors;
    }

    /**
     * Daftar objek pajak milik warga (untuk e-SPTD / self-assessment).
     * GET /api/citizen/tax-objects
     */
    public function taxObjects(Request $request)
    {
        $taxpayer = $this->authenticatedTaxpayer($request);

        $objects = TaxObject::with(['taxpayer', 'retributionType', 'opd', 'classification'])
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('created_at')
            ->get();

        // Warga berhak tahu ALASAN penolakan. Tanpa ini, halaman Detail Pengajuan
        // menampilkan status "Ditolak" tanpa catatan apa pun dari verifikator.
        // Mobile sudah membaca `latest_verification` + `verification_timeline`
        // (ServiceDetail.getRejectionNotes), jadi server wajib mengirimkannya.
        $objectIds = $objects->pluck('id')->all();

        $verificationsByObject = empty($objectIds)
            ? collect()
            : Verification::withoutGlobalScopes()
                ->with(['verifier:id,name'])
                ->whereIn('tax_object_id', $objectIds)
                ->orderBy('created_at')
                ->get()
                ->groupBy('tax_object_id');

        $objects->each(function (TaxObject $object) use ($verificationsByObject) {
            $timeline = $verificationsByObject->get($object->id, collect())
                ->map(fn ($verification) => [
                    'id' => $verification->id,
                    'document_number' => $verification->document_number,
                    'type' => $verification->type,
                    'status' => $verification->status,
                    'notes' => $verification->notes,
                    'submitted_at' => $verification->submitted_at,
                    'verified_at' => $verification->verified_at,
                    'verifier_name' => $verification->verifier?->name,
                ])
                ->values();

            // `latest_verification` harus = KEPUTUSAN VERIFIKATOR terakhir, bukan
            // catatan sistem pendaftaran. Catatan pendaftaran ("Pengajuan objek dari
            // menu Wajib Pajak...") tidak punya verifier dan bisa menutupi alasan
            // penolakan admin. Prioritas: entri dgn verifier + ada catatan.
            $withVerifier = $timeline->filter(
                fn ($item) => ! empty($item['notes']) && ! empty($item['verifier_name'])
            );
            $latest = $withVerifier->last()
                ?: $timeline->filter(fn ($item) => ! empty($item['notes']))->last()
                ?: $timeline->last();

            // Alasan penolakan terakhir dari verifikator — dikirim eksplisit supaya
            // tetap terbaca walau status objek sudah berubah (mis. warga sudah
            // mengajukan ulang sehingga status kembali `pending`).
            $lastRejection = $timeline
                ->filter(fn ($item) => $item['status'] === 'rejected' && ! empty($item['notes']))
                ->last();

            $object->setAttribute('verification_timeline', $timeline);
            $object->setAttribute('latest_verification', $latest);
            $object->setAttribute('rejection_notes', $lastRejection['notes'] ?? null);
            $object->setAttribute('rejected_at', $lastRejection['verified_at'] ?? null);
        });

        return response()->json(['data' => $objects]);
    }

    /**
     * Get bills for a specific classification (aggregated across all objects)
     */
    public function bills(Request $request, $id)
    {
        $taxpayer = $this->authenticatedTaxpayer($request);
        $classification = RetributionClassification::findOrFail($id);

        $objectIds = TaxObject::where('taxpayer_id', $taxpayer->id)
            ->where('retribution_classification_id', $classification->id)
            ->where('status', 'active')
            ->pluck('id');

        $bills = Bill::whereIn('tax_object_id', $objectIds)
            ->with(['opd:id,name', 'retributionType:id,name,icon', 'taxObject'])
            ->latest()
            ->get();

        return response()->json(['data' => $bills]);
    }

    /**
     * Get all pending billing periods for the logged-in citizen across all objects
     */
    public function getPendingPeriods(Request $request)
    {
        $user = $this->authenticatedTaxpayer($request);

        $objects = TaxObject::where('taxpayer_id', $user->id)
            ->where('status', 'active')
            ->with(['retributionType', 'classification', 'opd'])
            ->get();

        $billingService = app(BillingService::class);
        $allPending = collect();

        foreach ($objects as $obj) {
            $periods = $billingService->getPendingPeriods($obj);
            foreach ($periods as $period) {
                $allPending->push(array_merge($period, [
                    'id' => 'VIRTUAL-'.$obj->id.'-'.$period['period'],
                    'tax_object_id' => $obj->id,
                    'tax_object' => $obj,
                    'retribution_type' => $obj->retributionType,
                    'classification' => $obj->classification,
                    'opd' => $obj->opd,
                ]));
            }
        }

        return response()->json(['data' => $allPending]);
    }

    /**
     * Cari objek pajak / retribusi terdaftar (berdasarkan NOP, NPWPD, nama usaha, atau NOPD)
     * Digunakan untuk alur "Cek & Klaim Dulu" di aplikasi warga.
     */
    public function objectLookup(Request $request)
    {
        $citizen = $this->authenticatedTaxpayer($request);

        $request->validate([
            'q' => 'required|string|min:2',
            'classification_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $q = trim((string) $request->input('q'));
        $classificationId = $request->input('classification_id');
        $limit = (int) $request->input('limit', 15);
        if ($limit < 1 || $limit > 50) {
            $limit = 15;
        }

        $query = TaxObject::query()
            ->with([
                'taxpayer',
                'classification',
                'retributionType',
                'bills' => function ($b) {
                    $b->whereIn('status', ['unpaid', 'pending', 'overdue']);
                },
            ]);

        if ($classificationId) {
            $query->where('retribution_classification_id', $classificationId);
        }

        // Cari berdasarkan: NOP (legacy/baru), Nama Objek Usaha, Alamat, atau NPWPD/Nama Wajib Pajak
        $query->where(function ($sub) use ($q) {
            $sub->where('nop', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhere('address', 'like', "%{$q}%")
                ->orWhereHas('taxpayer', function ($tpQ) use ($q) {
                    $tpQ->where('npwpd', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
        });

        $objects = $query->take($limit)->get();

        $results = $objects->map(function (TaxObject $obj) use ($citizen) {
            $linkedTaxpayer = $obj->taxpayer;

            // Claimable jika terhubung ke taxpayer hasil impor legacy (belum punya google_id dan belum punya email)
            // ATAU belum punya taxpayer sama sekali
            $isUnclaimedLegacy = $linkedTaxpayer
                && empty($linkedTaxpayer->google_id)
                && empty($linkedTaxpayer->email);

            $isMine = ($obj->taxpayer_id === $citizen->id);

            $isClaimable = (! $isMine) && ($isUnclaimedLegacy || empty($obj->taxpayer_id));

            $unpaidBills = $obj->bills ?? collect();
            $unpaidCount = $unpaidBills->count();
            $totalUnpaid = (float) $unpaidBills->sum('amount');

            // Ekstrak info legacy dari metadata bila ada
            $metadata = $obj->metadata ?? [];
            $legacyTable = $metadata['legacy_table'] ?? null;
            $legacyTarif = $metadata['tarif_pajak'] ?? null;

            return [
                'id' => $obj->id,
                'name' => $obj->name,
                'nop' => $obj->nop,
                'address' => $obj->address,
                'district' => $obj->district,
                'sub_district' => $obj->sub_district,
                'classification_id' => $obj->retribution_classification_id,
                'classification_name' => $obj->classification?->name,
                'retribution_type_name' => $obj->retributionType?->name,
                'taxpayer_name' => $isMine
                    ? ($citizen->name ?? 'Anda')
                    : NopHelper::maskName($linkedTaxpayer?->name ?? 'Wajib Pajak'),
                'npwpd' => $linkedTaxpayer?->npwpd,
                'is_claimable' => $isClaimable,
                'is_mine' => $isMine,
                'claimed_status' => $isMine ? 'mine' : ($isClaimable ? 'unclaimed' : 'claimed_by_other'),
                'legacy_table' => $legacyTable,
                'legacy_tarif' => $legacyTarif,
                'unpaid_bills_count' => $unpaidCount,
                'total_unpaid_amount' => $totalUnpaid,
            ];
        });

        return response()->json([
            'success' => true,
            'query' => $q,
            'count' => $results->count(),
            'data' => $results,
        ]);
    }

    /**
     * Klaim kepemilikan objek pajak legacy ke akun warga yang login.
     */
    public function claimObject(Request $request)
    {
        $citizen = $this->authenticatedTaxpayer($request);

        $request->validate([
            'tax_object_id' => 'required|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        if (empty($citizen->nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi NIK Anda pada profil akun terlebih dahulu sebelum mengklaim objek pajak.',
            ], 422);
        }

        $taxObject = TaxObject::with('taxpayer')->findOrFail($request->input('tax_object_id'));

        // Jika objek sudah milik akun ini
        if ($taxObject->taxpayer_id === $citizen->id) {
            return response()->json([
                'success' => true,
                'message' => 'Objek pajak ini sudah terdaftar di akun Anda.',
                'data' => $taxObject,
            ]);
        }

        $linkedTaxpayer = $taxObject->taxpayer;
        $isUnclaimedLegacy = $linkedTaxpayer
            && empty($linkedTaxpayer->google_id)
            && empty($linkedTaxpayer->email);

        $isClaimable = ($isUnclaimedLegacy || empty($taxObject->taxpayer_id));

        if (! $isClaimable) {
            return response()->json([
                'success' => false,
                'message' => 'Objek pajak ini sudah diklaim oleh akun warga lain. Silakan hubungi loket Bapenda jika Anda adalah pemilik yang sah.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $previousTaxpayerId = $taxObject->taxpayer_id;
            $metadata = $taxObject->metadata ?? [];
            $metadata['claimed_at'] = now()->toIso8601String();
            $metadata['claimed_by_taxpayer_id'] = $citizen->id;
            $metadata['previous_taxpayer_id'] = $previousTaxpayerId;
            if ($request->filled('notes')) {
                $metadata['claim_notes'] = $request->input('notes');
            }

            $taxObject->taxpayer_id = $citizen->id;
            $taxObject->metadata = $metadata;
            $taxObject->save();

            // Jika objek memiliki tagihan atas nama taxpayer legacy lama, alihkan ke warga
            if ($previousTaxpayerId) {
                Bill::where('tax_object_id', $taxObject->id)
                    ->where('taxpayer_id', $previousTaxpayerId)
                    ->update(['taxpayer_id' => $citizen->id]);
            }

            // Jika akun warga belum memiliki NPWPD tapi objek legacy memiliki NPWPD, sinkronkan ke akun warga
            if (empty($citizen->npwpd) && ! empty($linkedTaxpayer?->npwpd)) {
                $citizen->npwpd = $linkedTaxpayer->npwpd;
                $citizen->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Objek '{$taxObject->name}' berhasil diklaim dan ditambahkan ke akun Anda.",
                'data' => $taxObject->load(['classification', 'retributionType', 'taxpayer']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal mengklaim objek pajak: ' . $e->getMessage(), [
                'citizen_id' => $citizen->id,
                'tax_object_id' => $taxObject->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses klaim objek. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    private function authenticatedTaxpayer(Request $request): Taxpayer
    {
        $user = $request->user();

        abort_unless($user instanceof Taxpayer, 403, 'Endpoint ini hanya untuk wajib pajak.');

        return $user;
    }

    private function resolvePrimaryProofUrl(array $metadata): ?string
    {
        $isMaps = function ($url) {
            return is_string($url) && (
                str_contains($url, 'maps.google.com') ||
                str_contains($url, 'google.com/maps') ||
                str_contains($url, 'goo.gl') ||
                str_contains($url, 'openstreetmap.org')
            );
        };

        $isMediaFile = function ($url) use ($isMaps) {
            if (! is_string($url) || $isMaps($url)) {
                return false;
            }

            return str_starts_with($url, 'http://') ||
                   str_starts_with($url, 'https://') ||
                   str_starts_with($url, '/storage') ||
                   str_starts_with($url, 'storage/') ||
                   str_contains($url, 'cloudinary');
        };

        $isImage = function ($url) use ($isMediaFile) {
            if (! $isMediaFile($url)) {
                return false;
            }

            return (bool) preg_match('/\.(jpe?g|png|webp|gif|svg)(\?.*)?$/i', $url) || str_contains($url, '/image/upload/');
        };

        // 1. Prioritas dokumen visual bangunan / fisik objek
        $priorityKeys = [
            'foto_bangunan',
            'foto_gedung',
            'foto_lokasi_open_kamera',
            'foto_lokasi',
            'foto_titik',
            'foto_objek',
            'foto_tempat',
            'foto_usaha',
            'materi_reklame',
            'foto_reklame',
            'formulir_data_dukung',
            'bukti_lahan',
        ];

        foreach ($priorityKeys as $key) {
            if (! empty($metadata[$key]) && $isImage($metadata[$key])) {
                return $metadata[$key];
            }
        }

        // 2. Cari gambar apapun di metadata
        foreach ($metadata as $val) {
            if ($isImage($val)) {
                return $val;
            }
        }

        // 3. Cari berkas media dokumen lain (misalnya PDF)
        foreach ($metadata as $val) {
            if ($isMediaFile($val)) {
                return $val;
            }
        }

        return null;
    }
}

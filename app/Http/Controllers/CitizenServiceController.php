<?php

namespace App\Http\Controllers;

use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\TaxpayerPbbObject;
use App\Models\PbbNopApplication;
use App\Models\Bill;
use App\Models\Verification;
use App\Services\RequirementFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CitizenServiceController extends Controller
{
    /**
     * List all active classifications for the logged-in citizen
     */
    public function index(Request $request)
    {
        $taxpayer = $request->user();
        
        if (!$taxpayer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $classifications = \App\Models\RetributionClassification::active()
            ->whereHas('retributionType', function($q) {
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
        $taxpayer = $request->user();
        
        if (!$taxpayer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $classification = \App\Models\RetributionClassification::with(['retributionType.opd'])
            ->findOrFail($id);

        // Layanan dinonaktifkan sementara (contoh: 201 Kesehatan menunggu alur BLUD)
        if (array_key_exists('is_active', $classification->getAttributes()) && !$classification->is_active) {
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
        $hasFlatTariff = !$isFlatOnly || $flatRates->isNotEmpty();

        return response()->json([
            'data' => [
                'id' => $classification->id,
                'name' => $classification->name,
                'type_id' => $service->id,
                'type_name' => $service->name,
                'category' => $service->category,
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
            ]
        ]);
    }

    /**
     * Register a new tax object for a classification
     */
    public function register(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $classification = \App\Models\RetributionClassification::with('retributionType')->findOrFail($id);

        // Layanan dinonaktifkan sementara — tolak pendaftaran objek baru
        if (array_key_exists('is_active', $classification->getAttributes()) && !$classification->is_active) {
            return response()->json(['message' => 'Layanan ini untuk sementara tidak tersedia.'], 404);
        }

        $service = $classification->retributionType;

        // GATE NIB (PTSP-13): layanan usaha wajib NIB
        \App\Http\Controllers\NibPublicController::enforceNibGate($request, $classification, $taxpayer);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'zone_id' => 'nullable|exists:zones,id',
            'metadata' => 'nullable',
        ]);

        $cloudinary = app(\App\Services\CloudinaryService::class);
        $metadata = $request->input('metadata', []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Upload field bertipe 'upload'/'file' dari form_schema (foto_lokasi, foto_usaha, KTP, dll)
        // Fix 28 Agu 2026: sebelumnya field ini dirender textbox & tidak pernah diproses sebagai file.
        foreach ($classification->form_schema ?? [] as $schemaField) {
            $fkey = $schemaField['key'] ?? null;
            $ftype = $schemaField['type'] ?? null;
            if (!$fkey || !in_array($ftype, ['upload', 'file'], true)) continue;
            if ($request->hasFile($fkey)) {
                $request->validate([
                    $fkey => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
                ]);
                $metadata[$fkey] = $cloudinary->upload(
                    $request->file($fkey),
                    'citizen/documents/' . $service->id
                );
            }
        }

        $metadata = $this->applySchemaDefaults($classification->form_schema ?? [], $metadata);
        $missingMetadata = $this->missingRequiredMetadata($classification->form_schema ?? [], $metadata);
        if (!empty($missingMetadata)) {
            throw ValidationException::withMessages($missingMetadata);
        }

        $isPbb = (stripos($classification->name ?? '', 'pbb') !== false) || ($classification->id == 186);
        $nop = $metadata['nop_pbb'] ?? null;
        if ($isPbb && !empty($nop)) {
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
            try {
                $inquiry = app(\App\Services\PbbBapendaService::class)->inquiryWithFallback($nop, date('Y'));
                if (($inquiry['status'] ?? 0) === 200) {
                    if (!empty($inquiry['nama_wp'])) {
                        $metadata['nama_wp'] = $inquiry['nama_wp'];
                    }
                    if (!empty($inquiry['alamat_wp'])) {
                        $metadata['alamat_wp'] = $inquiry['alamat_wp'];
                    }
                    if (!empty($inquiry['kelurahan'])) {
                        $metadata['kelurahan'] = $inquiry['kelurahan'];
                    }
                    if (!empty($inquiry['kota'])) {
                        $metadata['kota'] = $inquiry['kota'];
                    }
                    $metadata['pbb_pokok'] = (float) ($inquiry['pbb_pokok'] ?? $metadata['pbb_pokok'] ?? 0);
                    $metadata['njop_total'] = (float) ($inquiry['njop_total'] ?? $metadata['njop_total'] ?? 0);
                    $metadata['luas_bumi'] = (float) ($inquiry['luas_bumi'] ?? $metadata['luas_bumi'] ?? 0);
                    $metadata['luas_bangunan'] = (float) ($inquiry['luas_bangunan'] ?? $metadata['luas_bangunan'] ?? 0);
                    $metadata['data_source'] = $inquiry['source'] ?? 'simpbb';
                    $metadata['h2h_verified_at'] = now()->toISOString();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Zero-trust PBB inquiry lookup skipped/failed: ' . $e->getMessage());
            }
        }

        // Handle dynamic document uploads based on requirements from this classification
        $requirements = $classification->requirements ?? [];
        $missingFiles = $this->missingRequiredFiles($request, $requirements);
        if (!empty($missingFiles)) {
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
                    'citizen/documents/' . $service->id
                );
                $processedKeys[] = $key;
            }
        }

        // Safety fallback: Handle common keys from mobile app if they were sent but not in requirements
        $fallbacks = ['foto_lokasi_open_kamera', 'formulir_data_dukung'];
        foreach ($fallbacks as $key) {
            if (!in_array($key, $processedKeys) && $request->hasFile($key)) {
                $request->validate([
                    $key => $requirementFiles->rulesFor([
                        'key' => $key,
                        'type' => $key === 'foto_lokasi_open_kamera' ? 'image' : 'document',
                    ])
                ]);
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key), 
                    'citizen/documents/' . $service->id
                );
            }
        }

        [$taxObject, $verification] = DB::transaction(function () use ($request, $taxpayer, $classification, $service, $metadata, $isPbb) {
            // Create the tax object
            // Lengkapi kolom tabel dari metadata — halaman verifikasi admin Bapenda
            // membaca kolom tabel, bukan metadata. Sebelum ini luas/kelurahan/
            // penanggung jawab KOSONG walau datanya ada di hasil inquiry H2H,
            // sehingga verifikator tidak bisa menilai kewajaran objek.
            $pick = function (array $keys, $default = null) use ($metadata, $request) {
                foreach ($keys as $k) {
                    if (!empty($metadata[$k])) return $metadata[$k];
                }
                foreach ($keys as $k) {
                    $v = $request->input($k);
                    if (!empty($v)) return $v;
                }
                return $default;
            };

            $num = function ($v) {
                if ($v === null || $v === '') return null;
                return is_numeric($v) ? (float) $v : (float) preg_replace('/[^0-9.]/', '', (string) $v);
            };

            $taxObject = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $service->id,
                'retribution_classification_id' => $classification->id,
                'opd_id' => $service->opd_id,
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'metadata' => $metadata,
                'status' => 'pending',

                // --- Kelengkapan untuk verifikasi admin Bapenda ---
                'land_size_m2' => $num($pick(['luas_bumi', 'land_size_m2', 'luas_tanah'])),
                'building_size_m2' => $num($pick(['luas_bangunan', 'building_size_m2'])),
                'sub_district' => $pick(['kelurahan', 'sub_district']),
                'district' => $pick(['kecamatan', 'kota', 'district']),
                'penanggung_jawab_nama' => $pick(['nama_wp', 'penanggung_jawab_nama'], $request->name),
                'penanggung_jawab_kontak' => $pick(['kontak_wp', 'no_hp', 'telepon', 'penanggung_jawab_kontak']),
                'nama_penandatangan' => $pick(['nama_penandatangan'], $request->name),
                'tanggal_pernyataan' => $pick(['tanggal_pernyataan']) ?: now()->toDateString(),
                'nomor_formulir' => $pick(['nomor_formulir']) ?: ('SPOP-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)),
            ]);

            // Pick the first available file URL for the primary verification proof
            $firstFileUrl = null;
            foreach ($metadata as $val) {
                if (is_string($val) && (str_starts_with($val, 'http') || str_contains($val, 'cloudinary'))) {
                    $firstFileUrl = $val;
                    break;
                }
            }

            // Create a verification record for the admin to review
            $verification = Verification::create([
                'opd_id' => $service->opd_id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObject->id,
                'document_number' => 'REG-' . strtoupper(uniqid()),
                'taxpayer_name' => $taxpayer->name,
                'type' => 'Pendaftaran Objek',
                'amount' => 0,
                'status' => 'pending',
                'proof_file_url' => $firstFileUrl,
                'submitted_at' => Carbon::now(),
                'notes' => 'Pendaftaran unit baru (' . $classification->name . '): ' . $taxObject->name,
            ]);

            if (!$taxpayer->opd_id) {
                $taxpayer->update(['opd_id' => $service->opd_id]);
            }

            // PBB: auto-link ke TaxpayerPbbObject supaya objek langsung muncul di "Objek Saya" (pbb-tagihan)
            // Satu sumber objek PBB warga: daftar via layanan = otomatis bisa cek & bayar.
            if ($isPbb) {
                $nopLink = $metadata['nop_pbb'] ?? null;
                if (!empty($nopLink)) {
                    TaxpayerPbbObject::firstOrCreate(
                        ['taxpayer_id' => $taxpayer->id, 'nop' => $nopLink],
                        [
                            'name_on_sppt' => $metadata['nama_wp'] ?? $taxpayer->name ?? null,
                            'address_on_sppt' => $metadata['alamat_wp'] ?? $request->address ?? null,
                            'kelurahan' => $metadata['kelurahan'] ?? $request->sub_district ?? null,
                            'kota' => $metadata['kota'] ?? $request->district ?? null,
                            'is_verified' => true,
                            'notes' => 'Otomatis dari pendaftaran layanan PBB (REG-' . strtoupper(uniqid()) . ')',
                        ]
                    );
                }
            }

            return [$taxObject, $verification];
        });

        return response()->json([
            'message' => 'Pendaftaran unit berhasil dikirim dan menunggu verifikasi',
            'data' => [
                'object' => $taxObject,
                'verification_id' => $verification->id,
            ]
        ], 201);
    }

    private function applySchemaDefaults(array $schema, array $metadata): array
    {
        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if (!$key || array_key_exists($key, $metadata)) {
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
            if (!($field['required'] ?? false)) {
                continue;
            }

            $key = $field['key'] ?? null;
            if (!$key) {
                continue;
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
            if (!($requirement['required'] ?? false)) {
                continue;
            }

            $key = $requirement['key'] ?? null;
            if (!$key || $request->hasFile($key)) {
                continue;
            }

            // Identitas/KTP terpusat dari akun warga terdaftar (tidak perlu upload ulang)
            if (preg_match('/(ktp|identitas|nik)/i', (string) $key) || preg_match('/(ktp|identitas)/i', (string) ($requirement['name'] ?? $requirement['label'] ?? ''))) {
                if ($request->user()) {
                    continue;
                }
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
        $taxpayer = $request->user();

        if (!$taxpayer instanceof Taxpayer) {
            return response()->json(['message' => 'Endpoint ini hanya untuk wajib pajak.'], 403);
        }

        $objects = TaxObject::with(['taxpayer', 'retributionType', 'opd', 'classification'])
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $objects]);
    }

    /**
     * Get bills for a specific classification (aggregated across all objects)
     */
    public function bills(Request $request, $id)
    {
        $taxpayer = $request->user();

        if (!$taxpayer) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $classification = \App\Models\RetributionClassification::findOrFail($id);
        
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
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $objects = TaxObject::where('taxpayer_id', $user->id)
            ->where('status', 'active')
            ->with(['retributionType', 'classification', 'opd'])
            ->get();

        $billingService = app(\App\Services\BillingService::class);
        $allPending = collect();

        foreach ($objects as $obj) {
            $periods = $billingService->getPendingPeriods($obj);
            foreach ($periods as $period) {
                $allPending->push(array_merge($period, [
                    'id' => 'VIRTUAL-' . $obj->id . '-' . $period['period'],
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
}
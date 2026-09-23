<?php

namespace App\Http\Controllers;

use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaxpayerController extends Controller
{
    /**
     * List taxpayers (OPD-scoped for non-admin users)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Taxpayer::with(['opd', 'retributionTypes', 'retributionClassifications', 'creator', 'taxObjects']);

        // OPD isolation is enforced automatically by the authenticated
        // user's global Query Scope (see RetributionTypeScope), so there is
        // no need to manually add `where('opd_id', ...)` here anymore.
        if ($user && $user->role === 'petugas') {
            // Petugas only sees taxpayers whose chosen services (tax_objects or
            // taxpayer_retribution_type pivot) match their assignments.
            // `created_by` is intentionally NOT used - scoping is purely by assignments.
            $assignments = $user->assignments;
            if ($assignments->isNotEmpty()) {
                $query->where(function($masterQ) use ($assignments) {
                    $masterQ->orWhereHas('taxObjects', function($q) use ($assignments) {
                        $q->where(function($query) use ($assignments) {
                            foreach ($assignments as $assignment) {
                                $query->orWhere(function($sq) use ($assignment) {
                                    $sq->where('retribution_type_id', $assignment->retribution_type_id);
                                    if ($assignment->retribution_classification_id) {
                                        $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                                    }
                                });
                            }
                        });
                    });
                    $masterQ->orWhereHas('retributionTypes', function($q) use ($assignments) {
                        $q->where(function($query) use ($assignments) {
                            foreach ($assignments as $assignment) {
                                $query->orWhere(function($sq) use ($assignment) {
                                    $sq->where('retribution_types.id', $assignment->retribution_type_id);
                                    if ($assignment->retribution_classification_id) {
                                        $sq->where('taxpayer_retribution_type.retribution_classification_id', $assignment->retribution_classification_id);
                                    }
                                });
                            }
                        });
                    });
                });
            } else {
                // No assignments -> no assigned service to scope by
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('npwpd', 'like', "%{$search}%");
            });
        }

        $taxpayers = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($taxpayers);
    }

    /**
     * Store new taxpayer with retribution types
     */
    public function store(Request $request)
    {
        \Log::info('Taxpayer store request', [
            'user_id' => $request->user()?->id,
            'has_retribution_types' => $request->has('retribution_type_ids'),
        ]);
        $user = $request->user();
        $cloudinary = app(\App\Services\CloudinaryService::class);

        $request->validate([
            'nik' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'npwpd' => 'nullable|string|max:255',
            'object_name' => 'nullable|string|max:255',
            'object_address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'sometimes',
            'retribution_type_ids' => 'required|array|min:1',
            'retribution_type_ids.*' => 'exists:retribution_types,id',
            'retribution_classification_ids' => 'nullable|array',
            'retribution_classification_ids.*' => 'exists:retribution_classifications,id',
            'metadata' => 'nullable',
            'foto_lokasi_open_kamera' => 'nullable|image|max:5120',
            'formulir_data_dukung' => 'nullable|file|max:10240',
        ]);

        // Use user's OPD for non-super-admins, or require opd_id for super_admin
        if ($user->role === 'super_admin') {
            $request->validate(['opd_id' => 'required|exists:opds,id']);
            $opdId = $request->opd_id;
        } else {
            $opdId = $user->opd_id;
        }

        // Validate that retribution types belong to the same OPD
        $validTypesIds = (array)$request->retribution_type_ids;
        $validTypesCount = RetributionType::where('opd_id', $opdId)
            ->whereIn('id', $validTypesIds)
            ->count();
        
        if ($validTypesCount !== count($validTypesIds)) {
            return response()->json([
                'message' => 'Jenis retribusi harus milik OPD yang sama'
            ], 422);
        }

        // Handle Metadata & Files
        $metadata = $request->input('metadata', []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Dynamically handle all file uploads and add to metadata
        foreach ($request->allFiles() as $key => $file) {
            $folder = $key === 'foto_lokasi_open_kamera' ? 'taxpayers/survey' : 'taxpayers/docs';
            $metadata[$key] = $cloudinary->upload($file, $folder);
        }

        $typeIds = $validTypesIds;
        $classificationIds = (array)$request->input('retribution_classification_ids', []);
        $selectedClassifications = $this->validateObjectRegistrationPayload($request, $typeIds, $classificationIds, $metadata);

        // Check if taxpayer with this NIK already exists
        $taxpayer = null;
        if ($request->nik) {
            $taxpayer = Taxpayer::where('nik', $request->nik)->first();
        }

        // Resolve NPWPD automatically if not provided
        $npwpd = $request->npwpd ?: Taxpayer::resolveNpwpd($request->nik);

        if ($taxpayer) {
            // Update existing taxpayer basic info if provided
            $updateData = $request->only(['name', 'address', 'district', 'sub_district', 'phone']);
            $updateData['npwpd'] = $npwpd; // Use resolved NPWPD
            
            if ($request->filled('password')) {
                $updateData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
            }
            $taxpayer->update($updateData);
            
            // Merge metadata
            if (!empty($metadata)) {
                $existingMetadata = $taxpayer->metadata ?: [];
                $taxpayer->metadata = array_merge($existingMetadata, $metadata);
                $taxpayer->save();
            }
        } else {
            $taxpayer = Taxpayer::create([
                'opd_id' => $opdId,
                'nik' => $request->nik,
                'name' => $request->name,
                'address' => $request->address,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'phone' => $request->phone,
                'npwpd' => $npwpd, // Use resolved NPWPD
                'object_name' => $request->object_name,
                'object_address' => $request->object_address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_active' => $request->boolean('is_active', true),
                'metadata' => $metadata,
                'created_by' => $user->id,
                'password' => $request->password ? \Illuminate\Support\Facades\Hash::make($request->password) : null,
            ]);
        }

        // Attach retribution types and classifications
        foreach ($typeIds as $typeId) {
            // Get classification IDs that belong to this type
            $typeClassifications = $selectedClassifications
                ->where('retribution_type_id', $typeId)
                ->pluck('id')
                ->toArray();

            if (empty($typeClassifications)) {
                $typeClassifications = \App\Models\RetributionClassification::where('retribution_type_id', $typeId)
                ->whereIn('id', $classificationIds)
                ->pluck('id')
                ->toArray();
            }

            if (empty($typeClassifications)) {
                $taxpayer->retributionTypes()->syncWithoutDetaching([$typeId => ['retribution_classification_id' => null]]);
                
                // Also create/update TaxObject with metadata
                $this->syncTaxObject($taxpayer, $typeId, null, $metadata, $user);
            } else {
                foreach ($typeClassifications as $cId) {
                    $taxpayer->retributionTypes()->syncWithoutDetaching([$typeId => ['retribution_classification_id' => $cId]]);
                    
                    // Also create/update TaxObject with metadata
                    $this->syncTaxObject($taxpayer, $typeId, $cId, $metadata, $user);
                }
            }
        }

        return response()->json([
            'message' => 'Wajib pajak berhasil ditambahkan',
            'data' => $taxpayer->load([
                'opd',
                'retributionTypes',
                'retributionClassifications',
                'creator',
                'taxObjects.retributionType',
                'taxObjects.classification',
            ])
        ], 201);
    }

    /**
     * Show single taxpayer
     */
    public function show(Request $request, Taxpayer $taxpayer)
    {
        $user = $request->user();

        // All non-super-admins (OPD, Petugas) can only view their own OPD's taxpayers
        if (!$user->isSuperAdmin() && $taxpayer->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $relatedAssets = [];
        if ($taxpayer->nik) {
            $relatedAssets = \App\Models\TaxObject::whereHas('taxpayer', function($q) use ($taxpayer) {
                $q->where('nik', $taxpayer->nik);
            })
            ->where('taxpayer_id', '!=', $taxpayer->id)
            ->with(['taxpayer', 'retributionType', 'classification'])
            ->get();
        }

        $paymentHistory = \App\Models\Payment::where('taxpayer_id', $taxpayer->id)
            ->with(['bill', 'taxObject'])
            ->orderBy('paid_at', 'desc')
            ->get();

        return response()->json([
            'data' => $taxpayer->load([
                'opd',
                'retributionTypes',
                'retributionClassifications',
                'creator',
                'taxObjects.retributionType',
                'taxObjects.classification',
            ]),
            'related_assets' => $relatedAssets,
            'payment_history' => $paymentHistory
        ]);
    }

    /**
     * Update taxpayer
     */
    public function update(Request $request, Taxpayer $taxpayer)
    {
        \Log::info('Taxpayer update request', [
            'taxpayer_id' => $taxpayer->id,
            'user_id' => $request->user()?->id,
            'has_retribution_types' => $request->has('retribution_type_ids'),
        ]);
        $user = $request->user();
        $cloudinary = app(\App\Services\CloudinaryService::class);

        // All non-super-admins can only update their own OPD's taxpayers
        if ($user->role !== 'super_admin' && $taxpayer->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $request->validate([
                'nik' => 'nullable|string|max:20',
                'name' => 'sometimes|string|max:255',
                'address' => 'nullable|string',
                'district' => 'nullable|string|max:255',
                'sub_district' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'npwpd' => 'nullable|string|max:255',
                'object_name' => 'nullable|string|max:255',
                'object_address' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'is_active' => 'sometimes',
                'retribution_type_ids' => 'sometimes|array|min:1',
                'retribution_type_ids.*' => 'exists:retribution_types,id',
                'retribution_classification_ids' => 'sometimes|array',
                'retribution_classification_ids.*' => 'exists:retribution_classifications,id',
                'metadata' => 'nullable',
                'foto_lokasi_open_kamera' => 'nullable|image|max:5120',
                'formulir_data_dukung' => 'nullable|file|max:10240',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Taxpayer update validation failed', [
                'errors' => $e->errors(),
                'taxpayer_id' => $taxpayer->id,
                'user_id' => $request->user()?->id,
            ]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        $data = $request->only([
            'nik', 'name', 'address', 'district', 'sub_district', 'phone', 
            'object_name', 'object_address', 'latitude', 'longitude', 'is_active'
        ]);

        // Resolve NPWPD if not provided and not currently set, or if explicitly requested to update
        if ($request->has('npwpd')) {
            $data['npwpd'] = $request->npwpd;
        } elseif (!$taxpayer->npwpd) {
            $data['npwpd'] = Taxpayer::resolveNpwpd($request->nik ?: $taxpayer->nik);
        }

        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        // Handle Metadata & Files
        $metadata = $request->input('metadata', $taxpayer->metadata ?: []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        // Simpan seluruh file terunggah (termasuk key per-klasifikasi, mis. foto_lokasi_open_kamera__cls61)
        foreach ($request->allFiles() as $key => $file) {
            $folder = str_contains($key, 'foto') ? 'taxpayers/survey' : 'taxpayers/docs';
            $metadata[$key] = $cloudinary->upload($file, $folder);
        }

        $selectedClassifications = collect();
        if ($request->has('retribution_type_ids')) {
            $typeIds = (array)$request->retribution_type_ids;
            $classificationIds = (array)$request->input('retribution_classification_ids', []);
            $opdId = $taxpayer->opd_id;

            if (!$opdId && !empty($typeIds)) {
                $firstType = RetributionType::find($typeIds[0]);
                if ($firstType) {
                    $opdId = $firstType->opd_id;
                    $data['opd_id'] = $opdId;
                }
            }

            $validTypes = RetributionType::where('opd_id', $opdId)
                ->whereIn('id', $typeIds)
                ->count();

            if ($validTypes !== count($typeIds)) {
                return response()->json([
                    'message' => 'Jenis retribusi harus milik OPD yang sama'
                ], 422);
            }

            $selectedClassifications = $this->validateObjectRegistrationPayload($request, $typeIds, $classificationIds, $metadata, $taxpayer, false);
        }

        $data['metadata'] = $metadata;

        // Auto-resolve NPWPD if not set or NIK changed
        $nikChanged = isset($data['nik']) && $data['nik'] !== $taxpayer->nik;
        $npwpdEmpty = empty($data['npwpd'] ?? $taxpayer->npwpd);
        if ($nikChanged || $npwpdEmpty) {
            $data['npwpd'] = Taxpayer::resolveNpwpd(
                $data['nik'] ?? $taxpayer->nik,
                $data['npwpd'] ?? $taxpayer->npwpd
            );
        }

        try {
            $taxpayer->update($data);

            // Update retribution types if provided
            if ($request->has('retribution_type_ids')) {
                $typeIds = (array)$request->retribution_type_ids;
                $opdId = $taxpayer->opd_id;

                // If taxpayer has no OPD (orphaned legacy data), derive it from the first retribution type
                if (!$opdId && !empty($typeIds)) {
                    $firstType = RetributionType::find($typeIds[0]);
                    if ($firstType) {
                        $opdId = $firstType->opd_id;
                        $taxpayer->update(['opd_id' => $opdId]);
                    }
                }
                
                // Validate that retribution types belong to the same OPD
                $validTypes = RetributionType::where('opd_id', $opdId)
                    ->whereIn('id', $typeIds)
                    ->count();
                
                if ($validTypes !== count($typeIds)) {
                    return response()->json([
                        'message' => 'Jenis retribusi harus milik OPD yang sama'
                    ], 422);
                }

                $taxpayer->retributionTypes()->detach();
                
                $classificationIds = (array)$request->input('retribution_classification_ids', []);

                foreach ($typeIds as $typeId) {
                    $typeClassifications = RetributionClassification::where('retribution_type_id', $typeId)
                        ->whereIn('id', $classificationIds)
                        ->pluck('id')
                        ->toArray();

                    if (empty($typeClassifications)) {
                        $taxpayer->retributionTypes()->syncWithoutDetaching([$typeId => ['retribution_classification_id' => null]]);
                        $this->syncTaxObject($taxpayer, $typeId, null, $metadata, $user);
                    } else {
                        foreach ($typeClassifications as $cId) {
                            $taxpayer->retributionTypes()->syncWithoutDetaching([$typeId => ['retribution_classification_id' => $cId]]);
                            $this->syncTaxObject($taxpayer, $typeId, $cId, $metadata, $user);
                        }
                    }
                }
            }

            return response()->json([
                'message' => 'Wajib pajak berhasil diupdate',
                'data' => $taxpayer->fresh()->load([
                    'opd',
                    'retributionTypes',
                    'retributionClassifications',
                    'creator',
                    'taxObjects.retributionType',
                    'taxObjects.classification',
                ])
            ]);
        } catch (\Throwable $e) {
            \Log::error('Taxpayer Update Failed: ' . $e->getMessage(), [
                'taxpayer_id' => $taxpayer->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mengupdate wajib pajak: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Delete taxpayer
     */
    public function destroy(Request $request, Taxpayer $taxpayer)
    {
        $user = $request->user();

        // All non-super-admins can only delete their own OPD's taxpayers
        if (!$user->isSuperAdmin() && $taxpayer->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $taxpayer->delete();

        return response()->json([
            'message' => 'Wajib pajak berhasil dihapus'
        ]);
    }

    /**
     * Helper to sync taxpayer object info to tax_objects table
     */
    private function syncTaxObject(Taxpayer $taxpayer, $typeId, $classificationId = null, $metadata = [], $submitter = null)
    {
        // Priority: 1. Specific name in metadata for this classification, 2. Global object_name
        $specificName = null;
        if ($classificationId && isset($metadata["_object_name_{$classificationId}"])) {
            $specificName = $metadata["_object_name_{$classificationId}"];
        }
        
        $name = $specificName ?: $taxpayer->object_name;

        if (!$name) return;

        // Filter metadata to only include keys for this classification (backward-compat: raw keys & namespaced __cls{id})
        $nsSuffix = $classificationId ? "__cls{$classificationId}" : null;
        $scopedMetadata = array_filter($metadata, function ($key) use ($classificationId, $nsSuffix) {
            if (!$classificationId) return true;
            return $key === "_object_name_{$classificationId}" || str_ends_with((string)$key, $nsSuffix);
        });
        if (empty($scopedMetadata)) $scopedMetadata = $metadata;

        // [OPTIMIZATION] Generate NOP using pre-fetched components to avoid repeated string manipulation
        $nopPrefix = $taxpayer->npwpd ?: 'NOP-' . str_pad($taxpayer->id, 4, '0', STR_PAD_LEFT);
        $nop = $nopPrefix . '-' . $typeId . ($classificationId ? '-' . $classificationId : '');

        $data = [
            'opd_id' => $taxpayer->opd_id,
            'name' => $name,
            'address' => $taxpayer->object_address ?: $taxpayer->address,
            'district' => $taxpayer->district,
            'sub_district' => $taxpayer->sub_district,
            'latitude' => $taxpayer->latitude,
            'longitude' => $taxpayer->longitude,
            'nop' => $nop,
            'metadata' => $scopedMetadata,
        ];

        try {
            $taxObject = TaxObject::withoutGlobalScopes()->firstOrNew([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $classificationId,
            ]);

            $isNew = !$taxObject->exists;
            $shouldReopenVerification = $isNew || $taxObject->status === 'rejected';

            $taxObject->fill($data);

            if ($shouldReopenVerification) {
                $taxObject->status = 'pending';
                $taxObject->approved_at = null;
                $taxObject->approved_by = null;
            }

            $taxObject->save();

            $this->ensurePendingObjectVerification($taxObject, $taxpayer, $metadata, $submitter);

            return $taxObject;
        } catch (\Throwable $e) {
            // Fallback for NOP conflicts if they aren't covered by updateOrCreate (e.g. NOP changed but taxpayer combo same)
            \Log::warning('syncTaxObject conflict handled: ' . $e->getMessage());
            
            $fallback = TaxObject::withoutGlobalScopes()->where('nop', $nop)->first();
            if ($fallback) {
                $fallback->update($data);
                $this->ensurePendingObjectVerification($fallback, $taxpayer, $metadata, $submitter);
                return $fallback;
            }
            throw $e;
        }
    }

    private function validateObjectRegistrationPayload(
        Request $request,
        array $typeIds,
        array $classificationIds,
        array $metadata,
        ?Taxpayer $existingTaxpayer = null,
        bool $requireLocation = true
    )
    {
        $allClassificationsForTypes = RetributionClassification::whereIn('retribution_type_id', $typeIds)->get();
        $selectedClassifications = $allClassificationsForTypes->whereIn('id', $classificationIds)->values();
        $basicErrors = [];
        $fieldValue = fn (string $key) => $request->filled($key) ? $request->input($key) : ($existingTaxpayer?->{$key} ?? null);

        $invalidClassificationIds = array_values(array_diff(
            $classificationIds,
            $selectedClassifications->pluck('id')->map(fn ($id) => (int) $id)->all()
        ));

        if (!empty($invalidClassificationIds)) {
            throw ValidationException::withMessages([
                'retribution_classification_ids' => 'Klasifikasi harus sesuai dengan jenis retribusi yang dipilih.',
            ]);
        }

        // Klasifikasi penagihan otomatis (kode berawalan 'DENDA-', mis. denda overtime)
        // hanya dipakai sistem saat menghitung tagihan, bukan untuk registrasi objek baru.
        $penaltyClassification = $selectedClassifications->first(
            fn ($c) => str_starts_with((string) $c->code, 'DENDA-')
        );
        if ($penaltyClassification) {
            throw ValidationException::withMessages([
                'retribution_classification_ids' => 'Klasifikasi ' . $penaltyClassification->name . ' adalah klasifikasi penagihan otomatis dan tidak dapat dipilih untuk registrasi objek baru.',
            ]);
        }

        $missingTypeNames = [];
        foreach ($typeIds as $typeId) {
            $typeClassifications = $allClassificationsForTypes
                ->where('retribution_type_id', $typeId)
                ->reject(fn ($c) => str_starts_with((string) $c->code, 'DENDA-'));
            if ($typeClassifications->isNotEmpty() && $selectedClassifications->where('retribution_type_id', $typeId)->isEmpty()) {
                $typeName = RetributionType::whereKey($typeId)->value('name') ?: "ID {$typeId}";
                $missingTypeNames[] = $typeName;
            }
        }

        if (!empty($missingTypeNames)) {
            throw ValidationException::withMessages([
                'retribution_classification_ids' => 'Klasifikasi wajib dipilih untuk: ' . implode(', ', $missingTypeNames),
            ]);
        }

        if (empty($typeIds)) {
            $basicErrors['retribution_type_ids'] = 'Jenis retribusi wajib dipilih.';
        }

        if (!$fieldValue('object_name') && $selectedClassifications->isEmpty()) {
            $basicErrors['object_name'] = 'Nama objek/unit wajib diisi.';
        }

        foreach ($selectedClassifications as $classification) {
            $specificObjectName = $metadata["_object_name_{$classification->id}"] ?? null;
            if (!$fieldValue('object_name') && (!$specificObjectName || trim((string) $specificObjectName) === '')) {
                $basicErrors["metadata._object_name_{$classification->id}"] = "Nama objek/unit untuk {$classification->name} wajib diisi.";
            }
        }

        if (!$fieldValue('object_address') && !$fieldValue('address')) {
            $basicErrors['object_address'] = 'Alamat objek wajib diisi.';
        }

        if ($requireLocation && !$fieldValue('district')) {
            $basicErrors['district'] = 'Kecamatan objek wajib diisi.';
        }

        if ($requireLocation && !$fieldValue('sub_district')) {
            $basicErrors['sub_district'] = 'Kelurahan objek wajib diisi.';
        }

        if (!empty($basicErrors)) {
            throw ValidationException::withMessages($basicErrors);
        }

        $this->validateRequiredMetadataAndFiles($request, $selectedClassifications, $metadata);

        return $selectedClassifications;
    }

    private function validateRequiredMetadataAndFiles(Request $request, $classifications, array $metadata): void
    {
        $errors = [];

        foreach ($classifications as $classification) {
            $ns = fn (string $key) => "{$key}__cls{$classification->id}";

            foreach (($classification->form_schema ?? []) as $field) {
                if (!($field['required'] ?? false)) {
                    continue;
                }

                $key = $field['key'] ?? null;
                if (!$key) {
                    continue;
                }

                $value = $metadata[$ns($key)] ?? $metadata[$key] ?? null;
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $label = $field['label'] ?? $key;
                    $errors["metadata.{$key}"] = "{$label} wajib diisi.";
                }
            }

            foreach (($classification->requirements ?? []) as $requirement) {
                if (!($requirement['required'] ?? false)) {
                    continue;
                }

                $key = $requirement['key'] ?? null;
                if (!$key) {
                    continue;
                }

                $existingValue = $metadata[$ns($key)] ?? $metadata[$key] ?? null;
                if (!$request->hasFile($ns($key)) && !$request->hasFile($key) && !$existingValue) {
                    $label = $requirement['label'] ?? $requirement['name'] ?? $key;
                    $errors[$key] = "{$label} wajib diunggah.";
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function ensurePendingObjectVerification(TaxObject $taxObject, Taxpayer $taxpayer, array $metadata, $submitter = null): void
    {
        if ($taxObject->status !== 'pending') {
            return;
        }

        $hasOpenVerification = Verification::where('tax_object_id', $taxObject->id)
            ->whereIn('status', ['pending', 'in_review'])
            ->exists();

        if ($hasOpenVerification) {
            return;
        }

        Verification::create([
            'opd_id' => $taxObject->opd_id,
            'user_id' => $submitter?->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'document_number' => 'REG-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'taxpayer_name' => $taxpayer->name,
            'type' => 'Pendaftaran Objek',
            'amount' => 0,
            'status' => 'pending',
            'proof_file_url' => $this->firstUploadedFileUrl($metadata),
            'submitted_at' => Carbon::now(),
            'notes' => 'Pengajuan objek dari menu Wajib Pajak menunggu verifikasi.',
        ]);
    }

    private function firstUploadedFileUrl(array $metadata): ?string
    {
        foreach ($metadata as $value) {
            if (is_string($value) && (str_starts_with($value, 'http') || str_contains($value, 'cloudinary'))) {
                return $value;
            }
        }

        return null;
    }
}

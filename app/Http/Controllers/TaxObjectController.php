<?php

namespace App\Http\Controllers;

use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\User;
use App\Models\Verification;
use App\Services\RequirementFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaxObjectController extends Controller
{
    /**
     * List tax objects (OPD-scoped)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = TaxObject::with(['taxpayer', 'retributionType', 'opd', 'classification']);

        if ($user && ($user->role === 'opd' || $user->role === 'kabid_pengawas' || $user->role === 'kasubid_pengawas' || $user->role === 'admin' || $user->role === 'pengawas')) {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user && $user->role === 'petugas') {
            $query->where('opd_id', $user->opd_id);
            
            // Filter objects created by this Petugas (via Taxpayer relation or object relation)
            $query->whereHas('taxpayer', function($q) use ($user) {
                $q->where('created_by', $user->id);
            });

            $assignments = $user->assignments;
            if ($assignments && $assignments->count() > 0) {
                $query->where(function($q) use ($assignments) {
                    foreach ($assignments as $assignment) {
                        $q->orWhere(function($sq) use ($assignment) {
                            $sq->where('retribution_type_id', $assignment->retribution_type_id);
                            if ($assignment->retribution_classification_id) {
                                $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                            }
                        });
                    }
                });
            }
        }

        if ($request->has('retribution_type_id')) {
            $query->where('retribution_type_id', $request->retribution_type_id);
        }

        if ($request->has('taxpayer_id')) {
            $query->where('taxpayer_id', $request->taxpayer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nop', 'like', "%{$search}%")
                  ->orWhereHas('taxpayer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 50);
        if ($perPage == -1) {
            $objects = $query->latest()->get();
            return response()->json(['data' => $objects]);
        }

        $objects = $query->latest()->paginate($perPage);

        return response()->json($objects);
    }

    /**
     * Manually store a new tax object. Direct input still enters verification
     * so billing only starts after approval.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Authorization: Only OPD admins or Petugas can create tax objects directly here.
        if (!in_array($user->role, ['opd', 'petugas'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'taxpayer_id' => 'required|exists:taxpayers,id',
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'nullable|string|max:255',
            'sub_district' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'nop' => 'nullable|string|max:50',
        ]);

        // Ensure the retribution type belongs to the user's OPD
        $type = \App\Models\RetributionType::where('id', $request->retribution_type_id)
            ->where('opd_id', $user->opd_id)
            ->firstOrFail();

        // Ensure taxpayer belongs to OPD
        $taxpayer = \App\Models\Taxpayer::where('id', $request->taxpayer_id)
            ->where('opd_id', $user->opd_id)
            ->firstOrFail();

        $taxObject = TaxObject::create([
            'opd_id' => $user->opd_id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $request->retribution_classification_id,
            'name' => $request->name,
            'address' => $request->address,
            'district' => $request->district,
            'sub_district' => $request->sub_district,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'nop' => $request->nop,
            'status' => 'pending',
            'is_active' => true,
            'metadata' => [],
        ]);

        Verification::create([
            'opd_id' => $taxObject->opd_id,
            'user_id' => $user->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'document_number' => 'REG-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'taxpayer_name' => $taxpayer->name,
            'type' => 'Pendaftaran Objek',
            'amount' => 0,
            'status' => 'pending',
            'submitted_at' => now(),
            'notes' => 'Pengajuan objek dari input langsung menunggu verifikasi.',
        ]);

        return response()->json([
            'message' => 'Objek pajak berhasil diajukan dan menunggu verifikasi.',
            'data' => $taxObject
        ], 201);
    }

    /**
     * Display the specified tax object
     */
    public function show(TaxObject $taxObject)
    {
        $taxObject->load(['taxpayer', 'retributionType', 'opd', 'classification']);
        return response()->json($taxObject);
    }

    /**
     * Update a pending object or resubmit a rejected object into verification.
     */
    public function update(Request $request, TaxObject $taxObject)
    {
        $authorizationError = $this->authorizePendingObjectMutation($request, $taxObject);
        if ($authorizationError) {
            return $authorizationError;
        }

        $isRejectedResubmission = $taxObject->status === 'rejected';

        if (!in_array($taxObject->status, ['pending', 'rejected'], true)) {
            return response()->json(['message' => 'Hanya objek dengan status pending atau rejected yang dapat diedit.'], 422);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'district' => 'sometimes|string|max:255',
            'sub_district' => 'sometimes|string|max:255',
            'metadata' => 'nullable',
        ]);

        $metadata = $taxObject->metadata ?? [];
        $newMetadata = $request->input('metadata');
        if ($newMetadata) {
            if (is_string($newMetadata)) {
                $newMetadata = json_decode($newMetadata, true) ?: [];
            }
            $metadata = array_merge($metadata, $newMetadata);
        }

        // Handle dynamic document uploads
        $cloudinary = app(\App\Services\CloudinaryService::class);
        $requirementFiles = app(RequirementFileService::class);
        $taxObject->loadMissing(['classification', 'taxpayer']);
        $classification = $taxObject->classification;
        $requirements = $classification->requirements ?? [];
        $processedKeys = [];

        foreach ($requirements as $index => $req) {
            $key = $req['key'] ?? null;
            if ($key && $request->hasFile($key)) {
                $request->validate([$key => $requirementFiles->rulesFor($req, $index)]);
                $metadata[$key] = $cloudinary->upload(
                    $request->file($key), 
                    'citizen/documents/' . $taxObject->retribution_type_id
                );
                $processedKeys[] = $key;
            }
        }

        // Fallback files
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
                    'citizen/documents/' . $taxObject->retribution_type_id
                );
            }
        }

        if ($isRejectedResubmission) {
            $missingMetadata = $this->missingRequiredMetadata($classification->form_schema ?? [], $metadata);
            $missingFiles = $this->missingRequiredFiles($request, $requirements, $metadata);

            if (!empty($missingMetadata) || !empty($missingFiles)) {
                throw ValidationException::withMessages(array_merge($missingMetadata, $missingFiles));
            }
        }

        $updateData = [
            'name' => $request->input('name', $taxObject->name),
            'address' => $request->input('address', $taxObject->address),
            'district' => $request->input('district', $taxObject->district),
            'sub_district' => $request->input('sub_district', $taxObject->sub_district),
            'metadata' => $metadata,
        ];

        if ($isRejectedResubmission) {
            $updateData['status'] = 'pending';
            $updateData['approved_at'] = null;
            $updateData['approved_by'] = null;
        }

        DB::transaction(function () use ($request, $taxObject, $updateData, $metadata, $isRejectedResubmission) {
            $taxObject->update($updateData);

            if ($isRejectedResubmission) {
                $this->createResubmissionVerification($request, $taxObject->fresh(['taxpayer']), $metadata);
            }
        });

        return response()->json([
            'message' => $isRejectedResubmission
                ? 'Perbaikan data objek berhasil dikirim ulang dan menunggu verifikasi.'
                : 'Data objek berhasil diperbarui',
            'data' => $taxObject->fresh(['taxpayer', 'classification'])
        ]);
    }

    /**
     * Delete a pending tax object
     */
    public function destroy(Request $request, TaxObject $taxObject)
    {
        $authorizationError = $this->authorizePendingObjectMutation($request, $taxObject);
        if ($authorizationError) {
            return $authorizationError;
        }

        // Only allow deletion if status is pending
        if ($taxObject->status !== 'pending') {
            return response()->json(['message' => 'Hanya objek dengan status pending yang dapat dihapus.'], 422);
        }

        // Cleanup: Delete associated verifications
        \App\Models\Verification::where('tax_object_id', $taxObject->id)->delete();
        
        // Delete the object
        $taxObject->delete();

        return response()->json(['message' => 'Pengajuan objek berhasil dibatalkan dan dihapus']);
    }

    private function authorizePendingObjectMutation(Request $request, TaxObject $taxObject)
    {
        $user = $request->user();

        if ($user instanceof Taxpayer) {
            return (int) $taxObject->taxpayer_id === (int) $user->id
                ? null
                : response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user instanceof User) {
            if ($user->isSuperAdmin()) {
                return null;
            }

            if ($user->role === User::ROLE_OPD) {
                return (int) $taxObject->opd_id === (int) $user->opd_id
                    ? null
                    : response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 403);
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
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $label = $field['label'] ?? $key;
                $errors["metadata.{$key}"] = "{$label} wajib diisi sebelum pengajuan dikirim ulang.";
            }
        }

        return $errors;
    }

    private function missingRequiredFiles(Request $request, array $requirements, array $metadata): array
    {
        $errors = [];

        foreach ($requirements as $requirement) {
            if (!($requirement['required'] ?? false)) {
                continue;
            }

            $key = $requirement['key'] ?? null;
            if (!$key || $request->hasFile($key) || !empty($metadata[$key])) {
                continue;
            }

            $label = $requirement['label'] ?? $requirement['name'] ?? $key;
            $errors[$key] = "{$label} wajib diunggah sebelum pengajuan dikirim ulang.";
        }

        return $errors;
    }

    private function createResubmissionVerification(Request $request, TaxObject $taxObject, array $metadata): void
    {
        $user = $request->user();

        Verification::create([
            'opd_id' => $taxObject->opd_id,
            'user_id' => $user instanceof User ? $user->id : null,
            'taxpayer_id' => $taxObject->taxpayer_id,
            'tax_object_id' => $taxObject->id,
            'document_number' => 'REG-REV-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'taxpayer_name' => $taxObject->taxpayer?->name ?? 'Wajib Pajak',
            'type' => 'Perbaikan Pendaftaran Objek',
            'amount' => 0,
            'status' => 'pending',
            'proof_file_url' => $this->firstFileUrl($metadata),
            'submitted_at' => now(),
            'notes' => 'Perbaikan data setelah penolakan: ' . $taxObject->name,
        ]);
    }

    private function firstFileUrl(array $metadata): ?string
    {
        foreach ($metadata as $value) {
            if (is_string($value) && (str_starts_with($value, 'http') || str_starts_with($value, '/storage') || str_contains($value, 'cloudinary'))) {
                return $value;
            }
        }

        return null;
    }
}

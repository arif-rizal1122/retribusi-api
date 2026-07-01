<?php

namespace App\Http\Controllers;

use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
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
        
        $classifications = \App\Models\RetributionClassification::whereHas('retributionType', function($q) {
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
        
        $service = $classification->retributionType;
        
        $objects = TaxObject::where('taxpayer_id', $taxpayer->id)
            ->where('retribution_classification_id', $classification->id)
            ->with('zone')
            ->get();
        
        $objectIds = $objects->pluck('id');
        
        $bills = Bill::whereIn('tax_object_id', $objectIds)
            ->with(['opd:id,name', 'taxObject'])
            ->latest()
            ->get();

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
                'requirements' => $classification->requirements,
                'calculation_formula' => $classification->calculation_formula,
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
        $service = $classification->retributionType;

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'district' => 'required|string|max:255',
            'sub_district' => 'required|string|max:255',
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

        $metadata = $this->applySchemaDefaults($classification->form_schema ?? [], $metadata);
        $missingMetadata = $this->missingRequiredMetadata($classification->form_schema ?? [], $metadata);
        if (!empty($missingMetadata)) {
            throw ValidationException::withMessages($missingMetadata);
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

        [$taxObject, $verification] = DB::transaction(function () use ($request, $taxpayer, $classification, $service, $metadata) {
            // Create the tax object
            $taxObject = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $service->id,
                'retribution_classification_id' => $classification->id,
                'opd_id' => $service->opd_id,
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'address' => $request->address,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'metadata' => $metadata,
                'status' => 'pending',
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

            $label = $requirement['label'] ?? $requirement['name'] ?? $key;
            $errors[$key] = "{$label} wajib diunggah.";
        }

        return $errors;
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

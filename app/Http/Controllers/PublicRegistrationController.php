<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Verification;
use App\Services\RequirementFileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicRegistrationController extends Controller
{
    public function getTypes()
    {
        return response()->json(
            RetributionType::with('opd')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function getClassifications()
    {
        $classifications = RetributionClassification::with(['retributionType:id,name,opd_id,is_active'])
            ->whereHas('retributionType', fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function (RetributionClassification $classification) {
                if (!$classification->opd_id && $classification->retributionType) {
                    $classification->opd_id = $classification->retributionType->opd_id;
                }

                return $classification;
            });

        return response()->json($classifications);
    }

    public function getOpds()
    {
        return response()->json(
            Opd::where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function checkNik($nik, \App\Services\IdentityValidationService $validationService)
    {
        $validation = $validationService->validateNik($nik);
        if (!$validation['valid']) {
            return response()->json(['found' => false, 'message' => $validation['message']], 422);
        }

        $taxpayer = Taxpayer::with(['opd'])
            ->where('nik', $nik)
            ->orderByDesc('is_active')
            ->latest()
            ->first();

        if (!$taxpayer) {
            return response()->json(['found' => false]);
        }

        $assets = TaxObject::with(['opd:id,name', 'retributionType:id,name', 'classification:id,name'])
            ->whereHas('taxpayer', fn($q) => $q->where('nik', $nik))
            ->latest()
            ->get()
            ->map(fn(TaxObject $object) => [
                'id' => $object->id,
                'name' => $object->name,
                'address' => $object->address,
                'status' => $object->status,
                'opd' => $object->opd,
                'retribution_type' => $object->retributionType,
                'classification' => $object->classification,
            ]);

        return response()->json([
            'found' => true,
            'data' => [
                'id' => $taxpayer->id,
                'nik' => $taxpayer->nik,
                'name' => $taxpayer->name,
                'address' => $taxpayer->address,
                'phone' => $taxpayer->phone,
                'npwpd' => $taxpayer->npwpd,
                'district' => $taxpayer->district,
                'sub_district' => $taxpayer->sub_district,
                'is_active' => $taxpayer->is_active,
                'opd' => $taxpayer->opd,
            ],
            'count' => $assets->count(),
            'all_assets' => $assets
        ]);
    }

    public function register(
        Request $request,
        \App\Services\IdentityValidationService $validationService,
        RequirementFileService $requirementFiles
    )
    {
        $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'npwpd' => 'nullable|string',
            'object_name' => 'required|string|max:255',
            'object_address' => 'required|string',
            'district' => 'required|string',
            'sub_district' => 'required|string',
            'opd_id' => 'required|exists:opds,id',
            'retribution_classification_ids' => 'required|array',
            'retribution_classification_ids.*' => 'exists:retribution_classifications,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'metadata' => 'nullable',
        ]);

        $nikCheck = $validationService->validateNik($request->nik);
        if (!$nikCheck['valid']) {
            return response()->json(['message' => $nikCheck['message']], 422);
        }

        $metadata = $request->input('metadata', []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $classificationIds = array_values(array_unique(array_map('intval', $request->input('retribution_classification_ids', []))));
        $classifications = $this->resolveSelectedClassifications($classificationIds, (int) $request->opd_id);
        $metadata = $this->applySchemaDefaults($classifications, $metadata);
        $this->validateRequiredMetadataAndFiles($request, $classifications, $metadata);
        $metadata = $this->storeRequirementFiles($request, $classifications, $metadata, $requirementFiles);

        $result = DB::transaction(function () use ($request, $metadata, $classifications) {
            $opdId = (int) $request->opd_id;
            $taxpayer = Taxpayer::where('nik', $request->nik)
                ->where('opd_id', $opdId)
                ->first();

            $taxpayerData = [
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'npwpd' => Taxpayer::resolveNpwpd($request->nik, $request->npwpd),
                'object_name' => $request->object_name,
                'object_address' => $request->object_address,
                'latitude' => $request->latitude ?? -5.4632,
                'longitude' => $request->longitude ?? 122.6075,
            ];

            if ($taxpayer) {
                $taxpayer->update($taxpayerData);
            } else {
                $taxpayer = Taxpayer::create([
                    ...$taxpayerData,
                    'opd_id' => $opdId,
                    'nik' => $request->nik,
                    'is_active' => false,
                ]);
            }

            $objects = collect();
            foreach ($classifications as $classification) {
                $type = $classification->retributionType;
                $taxObject = TaxObject::create([
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classification->id,
                    'opd_id' => $opdId,
                    'name' => $request->object_name,
                    'address' => $request->object_address,
                    'district' => $request->district,
                    'sub_district' => $request->sub_district,
                    'latitude' => $request->latitude ?? -5.4632,
                    'longitude' => $request->longitude ?? 122.6075,
                    'metadata' => $metadata,
                    'status' => 'pending',
                    'is_active' => true,
                ]);

                $this->ensureTaxpayerClassification($taxpayer, $type->id, $classification->id);
                $this->createVerification($taxpayer, $taxObject, $classification, $metadata);

                $objects->push($taxObject);
            }

            return [
                'taxpayer' => $taxpayer->fresh(),
                'objects' => $objects,
            ];
        });

        return response()->json([
            'message' => 'Pendaftaran berhasil, menunggu verifikasi',
            'taxpayer' => $result['taxpayer'],
            'objects' => $result['objects'],
            'object' => $result['objects']->first(),
        ], 201);
    }

    private function resolveSelectedClassifications(array $classificationIds, int $opdId)
    {
        $classifications = RetributionClassification::with('retributionType')
            ->whereIn('id', $classificationIds)
            ->whereHas('retributionType', fn($q) => $q->where('is_active', true))
            ->get();

        if ($classifications->count() !== count($classificationIds)) {
            throw ValidationException::withMessages([
                'retribution_classification_ids' => 'Klasifikasi tidak valid atau jenis retribusinya tidak aktif.',
            ]);
        }

        $invalidOpd = $classifications->first(function (RetributionClassification $classification) use ($opdId) {
            $classificationOpdId = (int) ($classification->opd_id ?: $classification->retributionType?->opd_id);
            return $classificationOpdId !== $opdId;
        });

        if ($invalidOpd) {
            throw ValidationException::withMessages([
                'opd_id' => 'OPD harus sesuai dengan klasifikasi retribusi yang dipilih.',
            ]);
        }

        return $classifications->values();
    }

    private function applySchemaDefaults($classifications, array $metadata): array
    {
        foreach ($classifications as $classification) {
            foreach (($classification->form_schema ?? []) as $field) {
                $key = $field['key'] ?? null;
                if ($key && !array_key_exists($key, $metadata) && array_key_exists('default_value', $field)) {
                    $metadata[$key] = $field['default_value'];
                }
            }
        }

        return $metadata;
    }

    private function validateRequiredMetadataAndFiles(Request $request, $classifications, array $metadata): void
    {
        $errors = [];

        foreach ($classifications as $classification) {
            foreach (($classification->form_schema ?? []) as $field) {
                if (!($field['required'] ?? false)) {
                    continue;
                }

                $key = $field['key'] ?? null;
                $value = $key ? ($metadata[$key] ?? null) : null;

                if (!$key || $value === null || (is_string($value) && trim($value) === '')) {
                    $label = $field['label'] ?? $key ?? 'Field';
                    $errors["metadata.{$key}"] = "{$label} wajib diisi.";
                }
            }

            foreach (($classification->requirements ?? []) as $requirement) {
                if (!($requirement['required'] ?? false)) {
                    continue;
                }

                $key = $requirement['key'] ?? null;
                if (!$key || $request->hasFile($key) || !empty($metadata[$key])) {
                    continue;
                }

                $label = $requirement['label'] ?? $requirement['name'] ?? $key;
                $errors[$key] = "{$label} wajib diunggah.";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function storeRequirementFiles(
        Request $request,
        $classifications,
        array $metadata,
        RequirementFileService $requirementFiles
    ): array {
        $requirements = [];

        foreach ($classifications as $classification) {
            foreach (($classification->requirements ?? []) as $index => $requirement) {
                $key = $requirement['key'] ?? null;
                if ($key && !isset($requirements[$key])) {
                    $requirements[$key] = [$requirement, $index];
                }
            }
        }

        foreach ($requirements as $key => [$requirement, $index]) {
            if (!$request->hasFile($key)) {
                continue;
            }

            $request->validate([$key => $requirementFiles->rulesFor($requirement, $index)]);
            $path = $request->file($key)->store('public/requirements');
            $metadata[$key] = str_replace('public/', 'storage/', $path);
        }

        return $metadata;
    }

    private function ensureTaxpayerClassification(Taxpayer $taxpayer, int $typeId, int $classificationId): void
    {
        DB::table('taxpayer_retribution_type')->updateOrInsert(
            [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $classificationId,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function createVerification(
        Taxpayer $taxpayer,
        TaxObject $taxObject,
        RetributionClassification $classification,
        array $metadata
    ): void {
        Verification::create([
            'opd_id' => $taxObject->opd_id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'document_number' => 'REG-PUB-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'taxpayer_name' => $taxpayer->name,
            'type' => 'Pendaftaran Objek',
            'amount' => 0,
            'status' => 'pending',
            'proof_file_url' => $this->firstUploadedFileUrl($metadata),
            'submitted_at' => Carbon::now(),
            'notes' => 'Pendaftaran mandiri public portal (' . $classification->name . '): ' . $taxObject->name,
        ]);
    }

    private function firstUploadedFileUrl(array $metadata): ?string
    {
        foreach ($metadata as $value) {
            if (is_string($value) && (str_starts_with($value, 'http') || str_starts_with($value, 'storage/') || str_contains($value, 'cloudinary'))) {
                return $value;
            }
        }

        return null;
    }
}

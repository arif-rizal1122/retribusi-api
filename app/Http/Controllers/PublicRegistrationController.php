<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicRegistrationController extends Controller
{
    public function getTypes()
    {
        return response()->json(RetributionType::where('is_active', true)->get());
    }

    public function getClassifications()
    {
        return response()->json(RetributionClassification::all());
    }

    public function getOpds()
    {
        return response()->json(Opd::all());
    }

    public function checkNik($nik, \App\Services\IdentityValidationService $validationService)
    {
        $validation = $validationService->validateNik($nik);
        if (!$validation['valid']) {
            return response()->json(['found' => false, 'message' => $validation['message']], 422);
        }

        $taxpayer = Taxpayer::with(['opd'])->where('nik', $nik)->first();
        if (!$taxpayer) {
            return response()->json(['found' => false]);
        }

        $assets = TaxObject::with(['opd', 'retributionTypes'])->where('taxpayer_id', $taxpayer->id)->get();

        return response()->json([
            'found' => true,
            'data' => $taxpayer,
            'count' => $assets->count(),
            'all_assets' => $assets
        ]);
    }

    public function register(Request $request, \App\Services\IdentityValidationService $validationService)
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
        ]);

        $nikCheck = $validationService->validateNik($request->nik);
        if (!$nikCheck['valid']) {
            return response()->json(['message' => $nikCheck['message']], 422);
        }

        if ($request->npwpd) {
            $npwpCheck = $validationService->validateNpwp($request->npwpd);
            if (!$npwpCheck['valid']) {
                return response()->json(['message' => $npwpCheck['message']], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Find or Create Taxpayer
            $taxpayer = Taxpayer::firstOrCreate(
                ['nik' => $request->nik],
                [
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'npwpd' => $request->npwpd,
                    'is_active' => false, // Pending verification
                    'opd_id' => reset($request->opd_id) ?? clone $request->opd_id // Taking the first if it's array, logic handled in real system
                ]
            );

            // Update if exists but some data changed
            if (!$taxpayer->wasRecentlyCreated) {
                $taxpayer->update([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'npwpd' => $request->npwpd,
                ]);
            }

            // Create Tax Object
            $taxObject = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'opd_id' => $request->opd_id,
                'name' => $request->object_name,
                'address' => $request->object_address,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'latitude' => $request->latitude ?? -5.4632,
                'longitude' => $request->longitude ?? 122.6075,
                'metadata' => $request->metadata,
                'status' => 'pending'
            ]);

            // Sync Classifications
            if ($request->has('retribution_classification_ids')) {
                // Here we get the types from the classifications
                $classifications = RetributionClassification::whereIn('id', $request->retribution_classification_ids)->get();
                $typeIds = $classifications->pluck('retribution_type_id')->unique()->toArray();
                
                $taxObject->retributionTypes()->sync($typeIds);
            }

            // Prepare requirements for file upload
            $requirements = [];
            if ($request->has('retribution_classification_ids')) {
                $classifications = RetributionClassification::whereIn('id', $request->retribution_classification_ids)->get();
                foreach ($classifications as $classification) {
                    if (is_array($classification->requirements)) {
                        foreach ($classification->requirements as $req) {
                            $requirements[$req['key']] = $req;
                        }
                    }
                }
            }

            // Handle file uploads based on requirements
            $filePaths = [];
            foreach ($requirements as $key => $req) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);
                    $path = $file->store('public/requirements');
                    $filePaths[$key] = str_replace('public/', 'storage/', $path);
                }
            }
            
            // Store file paths in metadata
            if (!empty($filePaths)) {
                $meta = json_decode($taxObject->metadata ?? '{}', true) ?? [];
                $meta['documents'] = $filePaths;
                $taxObject->update(['metadata' => json_encode($meta)]);
            }

            // Create Verification entry so Admin can approve
            \App\Models\Verification::create([
                'user_id' => \App\Models\User::where('role', 'admin')->where('opd_id', $request->opd_id)->first()->id ?? 1, // Fallback to super_admin or first admin
                'verifiable_type' => TaxObject::class,
                'verifiable_id' => $taxObject->id,
                'status' => 'pending',
                'notes' => 'Pendaftaran Mandiri (Public Portal)',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pendaftaran berhasil, menunggu verifikasi',
                'taxpayer' => $taxpayer,
                'object' => $taxObject
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mendaftar: ' . $e->getMessage()], 500);
        }
    }
}

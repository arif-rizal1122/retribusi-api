<?php

namespace App\Http\Controllers;

use App\Models\RetributionClassification;
use Illuminate\Http\Request;

class RetributionClassificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RetributionClassification::with(['opd', 'retributionType']);

        if ($user && $user->role === 'opd') {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user && $user->role === 'petugas') {
            $query->where('opd_id', $user->opd_id);
            
            $assignments = $user->assignments;
            if ($assignments && $assignments->count() > 0) {
                $query->where(function($q) use ($assignments) {
                    foreach ($assignments as $assignment) {
                        $q->orWhere(function($sq) use ($assignment) {
                            $sq->where('retribution_type_id', $assignment->retribution_type_id);
                            if ($assignment->retribution_classification_id) {
                                $sq->where('id', $assignment->retribution_classification_id);
                            }
                        });
                    }
                });
            }
        } elseif ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'form_schema' => 'nullable|string',
            'requirements' => 'nullable|string',
            'calculation_formula' => 'nullable|string',
        ]);

        $cloudinary = app(\App\Services\CloudinaryService::class);
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $cloudinary->upload($request->file('icon'), 'classifications');
        }

        $opdId = in_array($user->role, ['opd', 'petugas']) ? $user->opd_id : $request->opd_id;
        
        // Infer opd_id from retribution type if not provided (Super Admin)
        if (!$opdId && $user->role === 'super_admin') {
            $retributionType = \App\Models\RetributionType::find($request->retribution_type_id);
            if ($retributionType) {
                $opdId = $retributionType->opd_id;
            } else {
                $request->validate(['opd_id' => 'required|exists:opds,id']);
                $opdId = $request->opd_id;
            }
        }

        $form_schema = $request->form_schema;
        if (is_string($form_schema)) {
            $form_schema = json_decode($form_schema, true);
        }

        $requirements = $request->requirements;
        if (is_string($requirements)) {
            $requirements = json_decode($requirements, true);
        }

        try {
            $classification = RetributionClassification::create([
                'opd_id' => $opdId,
                'retribution_type_id' => $request->retribution_type_id,
                'name' => $request->name,
                'code' => $request->code,
                'icon' => $iconPath,
                'description' => $request->description,
                'form_schema' => $form_schema,
                'requirements' => $requirements,
                'calculation_formula' => $request->calculation_formula,
            ]);

            return response()->json([
                'message' => 'Klasifikasi berhasil ditambahkan',
                'data' => $classification->load(['opd', 'retributionType'])
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Classification Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal membuat klasifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(RetributionClassification $retributionClassification)
    {
        return response()->json(['data' => $retributionClassification->load(['opd', 'retributionType', 'zones', 'rates'])]);
    }

    public function update(Request $request, RetributionClassification $retributionClassification)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $retributionClassification->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $request->validate([
                'retribution_type_id' => 'sometimes|exists:retribution_types,id',
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:50',
                'description' => 'nullable|string',
                'icon' => 'nullable', // Allow string (URL) or file
                'form_schema' => 'nullable|string',
                'requirements' => 'nullable|string',
                'calculation_formula' => 'nullable|string',
            ]);

            if ($request->hasFile('icon')) {
                $request->validate(['icon' => 'image|max:2048']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $data = $request->all();

        if ($request->hasFile('icon')) {
            $cloudinary = app(\App\Services\CloudinaryService::class);
            $data['icon'] = $cloudinary->upload($request->file('icon'), 'classifications');
        }

        if ($request->has('form_schema')) {
            $data['form_schema'] = is_string($request->form_schema) ? json_decode($request->form_schema, true) : $request->form_schema;
        }
        if ($request->has('requirements')) {
            $data['requirements'] = is_string($request->requirements) ? json_decode($request->requirements, true) : $request->requirements;
        }

        try {
            $retributionClassification->update($data);

            return response()->json([
                'message' => 'Klasifikasi berhasil diupdate',
                'data' => $retributionClassification->load(['opd', 'retributionType'])
            ]);
        } catch (\Exception $e) {
            \Log::error('Classification Update Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui klasifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, RetributionClassification $retributionClassification)
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $retributionClassification->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $retributionClassification->delete();
        return response()->json(['message' => 'Klasifikasi berhasil dihapus']);
    }
}

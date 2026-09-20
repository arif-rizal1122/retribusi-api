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

    public function store(Request $request)
    {
        $isOther = $request->input('registered_for') === 'other';

        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|max:16',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'land_area' => 'required|numeric|min:1',
            'building_area' => 'nullable|numeric|min:0',
            'ktp_file' => $isOther
                ? 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
                : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'akte_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'imb_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'registered_for' => 'sometimes|in:self,other',
            'owner_nik' => 'nullable|string|max:16',
            'owner_name' => 'required_if:registered_for,other|nullable|string|max:255',
            'owner_address' => 'nullable|string',
            'nop' => 'nullable|string|max:20',
            'metadata' => 'nullable',
            'nib' => 'nullable|string|max:50',
            'znt_applied' => 'nullable|string|max:20',
            'nir_applied' => 'nullable|numeric',
            'is_bpn_verified' => 'nullable|boolean',
            'polygon_coordinates' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
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
            'building_area' => $request->building_area,
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

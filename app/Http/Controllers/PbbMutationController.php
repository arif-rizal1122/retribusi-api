<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PbbMutation;
use App\Models\PbbSplitItem;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\TaxpayerPbbObject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PbbMutationController extends Controller
{
    /**
     * Skenario 1: Kalkulator Piutang Proporsional Pemecahan Kavling
     * Formula: Piutang_Kavling = (Luas_Kavling / Luas_Induk) * Total_Piutang_NOP_Induk
     */
    public function calculateProportionalDebt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_land_area' => 'required|numeric|min:1',
            'split_land_area' => 'required|numeric|min:0.1',
            'total_debt' => 'nullable|numeric|min:0',
            'parent_nop' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $parentArea = (float) $request->parent_land_area;
        $splitArea = (float) $request->split_land_area;
        $totalDebt = (float) ($request->total_debt ?? 0);

        if ($splitArea >= $parentArea) {
            return response()->json([
                'success' => false,
                'message' => "Luas pecahan ({$splitArea} m²) tidak boleh melebihi atau sama dengan luas NOP induk ({$parentArea} m²).",
            ], 422);
        }

        $proportionalDebt = ($splitArea / $parentArea) * $totalDebt;
        $remainingArea = $parentArea - $splitArea;
        $remainingDebt = max(0, $totalDebt - $proportionalDebt);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_land_area' => $parentArea,
                'split_land_area' => $splitArea,
                'remaining_land_area' => round($remainingArea, 2),
                'total_parent_debt' => $totalDebt,
                'proportional_debt' => round($proportionalDebt, 2),
                'remaining_parent_debt' => round($remainingDebt, 2),
                'proportional_debt_formatted' => 'Rp ' . number_format($proportionalDebt, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Skenario 1: Pengajuan Pemecahan NOP & Kavlingan
     */
    public function storeSplitKavling(Request $request)
    {
        // Normalisasi data input sebelum validasi
        $rawSplitItems = $request->input('split_items', []);
        if (is_array($rawSplitItems)) {
            foreach ($rawSplitItems as &$item) {
                if (isset($item['target_nik'])) {
                    $item['target_nik'] = preg_replace('/[^0-9]/', '', (string) $item['target_nik']);
                }
                if (isset($item['target_name'])) {
                    $item['target_name'] = trim((string) $item['target_name']);
                }
            }
            unset($item);
            $request->merge(['split_items' => $rawSplitItems]);
        }

        if ($request->has('applicant_nik')) {
            $cleanedNik = preg_replace('/[^0-9]/', '', (string) $request->applicant_nik);
            if (strlen($cleanedNik) === 16) {
                $request->merge(['applicant_nik' => $cleanedNik]);
            }
        }

        $validator = Validator::make($request->all(), [
            'parent_nop' => 'required|string|max:25',
            'applicant_nik' => 'required|string|size:16',
            'applicant_name' => 'required|string|max:255',
            'applicant_phone' => 'nullable|string|max:20',
            'applicant_address' => 'nullable|string',
            'parent_land_area' => 'required|numeric|min:1',
            'total_parent_debt' => 'nullable|numeric|min:0',
            'split_items' => 'required|array|min:1',
            'split_items.*.target_nik' => 'required|string|size:16',
            'split_items.*.target_name' => 'required|string|max:255',
            'split_items.*.land_area_m2' => 'required|numeric|min:1',
            'split_items.*.building_area_m2' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'deed_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'site_plan_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'parent_nop.required' => 'Nomor NOP Induk wajib diisi.',
            'parent_nop.max' => 'Format NOP Induk maksimal 25 karakter.',
            'applicant_nik.required' => 'NIK Pemohon wajib diisi.',
            'applicant_nik.size' => 'NIK Pemohon harus terdiri dari 16 digit.',
            'applicant_name.required' => 'Nama Pemohon wajib diisi.',
            'parent_land_area.required' => 'Luas Tanah Induk wajib diisi.',
            'parent_land_area.min' => 'Luas Tanah Induk minimal 1 m².',
            'split_items.required' => 'Daftar kavling pecahan minimal 1 bidang.',
            'split_items.min' => 'Daftar kavling pecahan minimal 1 bidang.',
            'split_items.*.target_nik.required' => 'NIK Calon Pemilik wajib diisi.',
            'split_items.*.target_nik.size' => 'NIK Calon Pemilik kavling harus tepat 16 digit angka.',
            'split_items.*.target_name.required' => 'Nama Calon Pemilik kavling wajib diisi.',
            'split_items.*.land_area_m2.required' => 'Luas tanah pecahan kavling wajib diisi.',
            'split_items.*.land_area_m2.min' => 'Luas tanah kavling pecahan minimal 1 m².',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            Log::warning('PbbMutationController::storeSplitKavling validation failed', [
                'first_error' => $firstError,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422);
        }

        $parentArea = (float) $request->parent_land_area;
        $splitItems = $request->split_items;
        $totalSplitArea = array_sum(array_column($splitItems, 'land_area_m2'));

        // Asas Konservasi Luas: Luas Sisa Induk = Luas Induk - SUM(Luas Pecahan)
        if ($totalSplitArea >= $parentArea) {
            return response()->json([
                'success' => false,
                'message' => "Total luas seluruh pecahan ({$totalSplitArea} m²) tidak boleh melebihi atau sama dengan luas NOP Induk ({$parentArea} m²).",
            ], 422);
        }

        $totalParentDebt = (float) ($request->total_parent_debt ?? 0);
        $ticketNo = 'MUT-SPLIT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        $attachments = [];
        if ($request->hasFile('deed_document')) {
            $attachments['deed_document'] = $request->file('deed_document')->store('pbb/mutations/deed', 'public');
        }
        if ($request->hasFile('site_plan_document')) {
            $attachments['site_plan_document'] = $request->file('site_plan_document')->store('pbb/mutations/site_plan', 'public');
        }

        DB::beginTransaction();
        try {
            $mutation = PbbMutation::create([
                'ticket_no' => $ticketNo,
                'user_id' => $request->user()?->id,
                'mutation_type' => 'SPLIT_KAVLING',
                'applicant_nik' => $request->applicant_nik,
                'applicant_name' => $request->applicant_name,
                'applicant_phone' => $request->applicant_phone,
                'applicant_address' => $request->applicant_address,
                'parent_nop' => $request->parent_nop,
                'status' => 'PENDING',
                'notes' => $request->notes,
                'parent_land_area' => $parentArea,
                'total_parent_debt' => $totalParentDebt,
                'attachment_files' => $attachments,
                'metadata' => [
                    'split_count' => count($splitItems),
                    'total_split_area' => $totalSplitArea,
                    'remaining_parent_area' => $parentArea - $totalSplitArea,
                ],
            ]);

            $totalProportionalDebt = 0;
            foreach ($splitItems as $idx => $item) {
                $itemArea = (float) $item['land_area_m2'];
                $itemPropDebt = $parentArea > 0 ? ($itemArea / $parentArea) * $totalParentDebt : 0;
                $totalProportionalDebt += $itemPropDebt;

                PbbSplitItem::create([
                    'pbb_mutation_id' => $mutation->id,
                    'item_index' => $idx + 1,
                    'target_nik' => $item['target_nik'],
                    'target_name' => $item['target_name'],
                    'land_area_m2' => $itemArea,
                    'building_area_m2' => (float) ($item['building_area_m2'] ?? 0),
                    'proportional_debt_amount' => round($itemPropDebt, 2),
                    'status' => 'PENDING',
                ]);
            }

            $mutation->update(['proportional_debt_paid' => round($totalProportionalDebt, 2)]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permohonan pemecahan kavling PBB berhasil diajukan dengan nomor tiket ' . $ticketNo,
                'data' => $mutation->load('splitItems'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PBB Split Kavling Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Skenario 4: Permohonan Pembetulan SPPT (Rectification)
     */
    public function storeRectification(Request $request)
    {
        if ($request->has('applicant_nik')) {
            $cleanedNik = preg_replace('/[^0-9]/', '', (string) $request->applicant_nik);
            if (strlen($cleanedNik) === 16) {
                $request->merge(['applicant_nik' => $cleanedNik]);
            }
        }

        $validator = Validator::make($request->all(), [
            'parent_nop' => 'required|string|max:25',
            'applicant_nik' => 'required|string|size:16',
            'applicant_name' => 'required|string|max:255',
            'applicant_phone' => 'nullable|string|max:20',
            'correction_field' => 'required|string', // NAMA_WP, NIK, ALAMAT_WP, LUAS_BUMI, LUAS_BANGUNAN, MULTIPLE
            'old_data' => 'required|array',
            'new_data' => 'required|array',
            'notes' => 'nullable|string',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'parent_nop.required' => 'Nomor NOP pada SPPT wajib diisi.',
            'applicant_nik.required' => 'NIK Pemohon wajib diisi.',
            'applicant_nik.size' => 'NIK Pemohon harus terdiri dari 16 digit.',
            'applicant_name.required' => 'Nama Pemohon wajib diisi.',
            'correction_field.required' => 'Bidang yang ingin dibetulkan wajib dipilih.',
            'old_data.required' => 'Data lama sebelum pembetulan wajib disertakan.',
            'new_data.required' => 'Data baru hasil pembetulan wajib disertakan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticketNo = 'MUT-RECT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $attachments = [];
        if ($request->hasFile('supporting_document')) {
            $attachments['supporting_document'] = $request->file('supporting_document')->store('pbb/mutations/rectification', 'public');
        }

        $mutation = PbbMutation::create([
            'ticket_no' => $ticketNo,
            'user_id' => $request->user()?->id,
            'mutation_type' => 'RECTIFICATION',
            'applicant_nik' => $request->applicant_nik,
            'applicant_name' => $request->applicant_name,
            'applicant_phone' => $request->applicant_phone,
            'parent_nop' => $request->parent_nop,
            'status' => 'PENDING',
            'notes' => $request->notes,
            'correction_data' => [
                'correction_field' => $request->correction_field,
                'old_data' => $request->old_data,
                'new_data' => $request->new_data,
            ],
            'attachment_files' => $attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permohonan pembetulan data SPPT berhasil diajukan dengan nomor tiket ' . $ticketNo,
            'data' => $mutation,
        ], 201);
    }

    /**
     * Skenario 5: Mutasi Penuh / Balik Nama via BPHTB
     */
    public function storeTransferOwnership(Request $request)
    {
        foreach (['applicant_nik', 'previous_taxpayer_nik', 'new_taxpayer_nik'] as $nikField) {
            if ($request->has($nikField)) {
                $cleanedNik = preg_replace('/[^0-9]/', '', (string) $request->$nikField);
                if (strlen($cleanedNik) === 16) {
                    $request->merge([$nikField => $cleanedNik]);
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'parent_nop' => 'required|string|max:25',
            'applicant_nik' => 'required|string|size:16',
            'applicant_name' => 'required|string|max:255',
            'previous_taxpayer_nik' => 'required|string|size:16',
            'previous_taxpayer_name' => 'required|string|max:255',
            'new_taxpayer_nik' => 'required|string|size:16',
            'new_taxpayer_name' => 'required|string|max:255',
            'bphtb_ntpd_ref' => 'nullable|string|max:64',
            'notes' => 'nullable|string',
            'deed_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'parent_nop.required' => 'Nomor NOP wajib diisi.',
            'applicant_nik.required' => 'NIK Pemohon wajib diisi.',
            'applicant_nik.size' => 'NIK Pemohon harus 16 digit.',
            'previous_taxpayer_nik.required' => 'NIK Pemilik Lama wajib diisi.',
            'previous_taxpayer_nik.size' => 'NIK Pemilik Lama harus 16 digit.',
            'previous_taxpayer_name.required' => 'Nama Pemilik Lama wajib diisi.',
            'new_taxpayer_nik.required' => 'NIK Pemilik Baru wajib diisi.',
            'new_taxpayer_nik.size' => 'NIK Pemilik Baru harus 16 digit.',
            'new_taxpayer_name.required' => 'Nama Pemilik Baru wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticketNo = 'MUT-TRF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $attachments = [];
        if ($request->hasFile('deed_document')) {
            $attachments['deed_document'] = $request->file('deed_document')->store('pbb/mutations/transfer', 'public');
        }

        $mutation = PbbMutation::create([
            'ticket_no' => $ticketNo,
            'user_id' => $request->user()?->id,
            'mutation_type' => 'TRANSFER_OWNERSHIP',
            'applicant_nik' => $request->applicant_nik,
            'applicant_name' => $request->applicant_name,
            'parent_nop' => $request->parent_nop,
            'previous_taxpayer_nik' => $request->previous_taxpayer_nik,
            'previous_taxpayer_name' => $request->previous_taxpayer_name,
            'new_taxpayer_nik' => $request->new_taxpayer_nik,
            'new_taxpayer_name' => $request->new_taxpayer_name,
            'bphtb_ntpd_ref' => $request->bphtb_ntpd_ref,
            'status' => 'PENDING',
            'notes' => $request->notes,
            'attachment_files' => $attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permohonan balik nama PBB berhasil diajukan dengan nomor tiket ' . $ticketNo,
            'data' => $mutation,
        ], 201);
    }

    /**
     * Skenario 6: Penggabungan Objek Pajak (Amalgamation)
     */
    public function storeAmalgamation(Request $request)
    {
        if ($request->has('applicant_nik')) {
            $cleanedNik = preg_replace('/[^0-9]/', '', (string) $request->applicant_nik);
            if (strlen($cleanedNik) === 16) {
                $request->merge(['applicant_nik' => $cleanedNik]);
            }
        }

        $validator = Validator::make($request->all(), [
            'primary_target_nop' => 'required|string|max:25',
            'applicant_nik' => 'required|string|size:16',
            'applicant_name' => 'required|string|max:255',
            'merged_source_nops' => 'required|array|min:1',
            'merged_source_nops.*' => 'required|string|max:25',
            'notes' => 'nullable|string',
            'certificate_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'primary_target_nop.required' => 'Nomor NOP Utama wajib diisi.',
            'applicant_nik.required' => 'NIK Pemohon wajib diisi.',
            'applicant_nik.size' => 'NIK Pemohon harus 16 digit.',
            'merged_source_nops.required' => 'Daftar NOP yang akan digabung minimal 1 NOP.',
            'merged_source_nops.min' => 'Daftar NOP yang akan digabung minimal 1 NOP.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Jangan izinkan NOP utama ada di daftar NOP yang digabungkan
        if (in_array($request->primary_target_nop, $request->merged_source_nops)) {
            return response()->json([
                'success' => false,
                'message' => 'NOP Utama tidak boleh disertakan dalam daftar NOP sumber yang akan dilebur.',
            ], 422);
        }

        $ticketNo = 'MUT-MRG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $attachments = [];
        if ($request->hasFile('certificate_document')) {
            $attachments['certificate_document'] = $request->file('certificate_document')->store('pbb/mutations/amalgamation', 'public');
        }

        $mutation = PbbMutation::create([
            'ticket_no' => $ticketNo,
            'user_id' => $request->user()?->id,
            'mutation_type' => 'AMALGAMATION',
            'applicant_nik' => $request->applicant_nik,
            'applicant_name' => $request->applicant_name,
            'parent_nop' => $request->primary_target_nop,
            'primary_target_nop' => $request->primary_target_nop,
            'merged_source_nops_json' => array_values(array_unique($request->merged_source_nops)),
            'status' => 'PENDING',
            'notes' => $request->notes,
            'attachment_files' => $attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permohonan penggabungan NOP PBB berhasil diajukan dengan nomor tiket ' . $ticketNo,
            'data' => $mutation,
        ], 201);
    }

    /**
     * Skenario 7: Pembatalan Ketetapan & Objek Pajak Ganda (Cancellation)
     */
    public function storeCancellation(Request $request)
    {
        if ($request->has('applicant_nik')) {
            $cleanedNik = preg_replace('/[^0-9]/', '', (string) $request->applicant_nik);
            if (strlen($cleanedNik) === 16) {
                $request->merge(['applicant_nik' => $cleanedNik]);
            }
        }

        $validator = Validator::make($request->all(), [
            'duplicate_nop_target' => 'required|string|max:25',
            'applicant_nik' => 'required|string|size:16',
            'applicant_name' => 'required|string|max:255',
            'cancellation_reason_code' => 'required|string|in:DUPLICATE,DESTROYED_LAND,PUBLIC_FACILITY',
            'notes' => 'required|string|min:5',
            'statement_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'duplicate_nop_target.required' => 'Nomor NOP yang dimohonkan pembatalan wajib diisi.',
            'applicant_nik.required' => 'NIK Pemohon wajib diisi.',
            'applicant_nik.size' => 'NIK Pemohon harus 16 digit.',
            'cancellation_reason_code.required' => 'Alasan pembatalan wajib dipilih.',
            'notes.required' => 'Penjelasan / kronologi pembatalan wajib diisi minimal 5 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticketNo = 'MUT-CNL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $attachments = [];
        if ($request->hasFile('statement_document')) {
            $attachments['statement_document'] = $request->file('statement_document')->store('pbb/mutations/cancellation', 'public');
        }

        $mutation = PbbMutation::create([
            'ticket_no' => $ticketNo,
            'user_id' => $request->user()?->id,
            'mutation_type' => 'CANCELLATION',
            'applicant_nik' => $request->applicant_nik,
            'applicant_name' => $request->applicant_name,
            'parent_nop' => $request->duplicate_nop_target,
            'duplicate_nop_target' => $request->duplicate_nop_target,
            'cancellation_reason_code' => $request->cancellation_reason_code,
            'status' => 'PENDING',
            'notes' => $request->notes,
            'attachment_files' => $attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permohonan pembatalan/penghapusan NOP berhasil diajukan dengan nomor tiket ' . $ticketNo,
            'data' => $mutation,
        ], 201);
    }

    /**
     * Daftar Mutasi (Admin View)
     */
    public function index(Request $request)
    {
        $query = PbbMutation::with(['splitItems', 'user', 'assignedOfficer']);

        if ($request->filled('mutation_type')) {
            $query->where('mutation_type', strtoupper($request->mutation_type));
        }

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
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('ticket_no', 'like', "%{$s}%")
                    ->orWhere('parent_nop', 'like', "%{$s}%")
                    ->orWhere('applicant_nik', 'like', "%{$s}%")
                    ->orWhere('applicant_name', 'like', "%{$s}%")
                    ->orWhere('bphtb_ntpd_ref', 'like', "%{$s}%");
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        return response()->json($query->latest()->paginate($perPage));
    }

    /**
     * Riwayat Mutasi Wajib Pajak (Citizen View)
     */
    public function myApplications(Request $request)
    {
        $user = $request->user();
        $query = PbbMutation::with(['splitItems'])->latest();

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (!empty($user->nik)) {
                    $q->orWhere('applicant_nik', $user->nik);
                }
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Detail Mutasi Tunggal
     */
    public function show($id)
    {
        $mutation = PbbMutation::with(['splitItems', 'user', 'assignedOfficer', 'verifierL1', 'verifierL2', 'approverL3'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $mutation,
        ]);
    }

    /**
     * Matriks Verifikasi 3 Tingkat (Update Status):
     * Level 1 (Staf Pelayanan / Verifikator): VERIFIED_L1
     * Level 2 (Kasubid Pendataan): PRE_APPROVED_L2 atau disposisi SURVEY
     * Level 3 (Kabid Pajak Daerah): APPROVED (Final) atau REJECTED
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:VERIFIED_L1,PRE_APPROVED_L2,APPROVED,REJECTED,SURVEY',
            'notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'assigned_officer_id' => 'nullable|exists:users,id',
            'survey_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $mutation = PbbMutation::with('splitItems')->findOrFail($id);
        $user = $request->user();
        $newStatus = $request->status;

        if ($newStatus === 'REJECTED' && empty($request->rejection_reason)) {
            return response()->json(['success' => false, 'message' => 'Alasan penolakan wajib dicantumkan.'], 422);
        }

        DB::beginTransaction();
        try {
            $updateData = ['status' => $newStatus];

            if ($request->filled('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($newStatus === 'VERIFIED_L1') {
                $updateData['verified_l1_by'] = $user?->id;
                $updateData['verified_l1_at'] = now();
            } elseif ($newStatus === 'PRE_APPROVED_L2') {
                $updateData['verified_l2_by'] = $user?->id;
                $updateData['verified_l2_at'] = now();
            } elseif ($newStatus === 'SURVEY') {
                $updateData['assigned_officer_id'] = $request->assigned_officer_id;
                if ($request->filled('survey_notes')) {
                    $updateData['survey_notes'] = $request->survey_notes;
                }
            } elseif ($newStatus === 'REJECTED') {
                $updateData['rejection_reason'] = $request->rejection_reason;
            } elseif ($newStatus === 'APPROVED') {
                $updateData['approved_l3_by'] = $user?->id;
                $updateData['approved_l3_at'] = now();

                // Eksekusi Logika Fiskal Berdasarkan Skenario
                $this->executeScenarioApproval($mutation);
            }

            $mutation->update($updateData);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status permohonan mutasi PBB berhasil diperbarui menjadi ' . $newStatus,
                'data' => $mutation->fresh(['splitItems', 'assignedOfficer', 'verifierL1', 'verifierL2', 'approverL3']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Update Status PBB Mutation Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eksekusi Otomatisasi saat Level 3 (Kabid) menyetujui mutasi
     */
    private function executeScenarioApproval(PbbMutation $mutation): void
    {
        if ($mutation->mutation_type === 'SPLIT_KAVLING') {
            // Skenario 1: Terbitkan NOP unik untuk setiap kavling pecahan
            $parentNopClean = preg_replace('/[^0-9]/', '', (string) $mutation->parent_nop);
            $kec = strlen($parentNopClean) >= 7 ? substr($parentNopClean, 4, 3) : '020';
            $kel = strlen($parentNopClean) >= 10 ? substr($parentNopClean, 7, 3) : '001';

            foreach ($mutation->splitItems as $idx => $item) {
                $lastSeq = TaxObject::where('nop', 'like', "7472{$kec}{$kel}001%")
                    ->pluck('nop')
                    ->map(function ($val) {
                        $clean = preg_replace('/[^0-9]/', '', (string) $val);
                        return (strlen($clean) === 18) ? (int) substr($clean, 13, 4) : 0;
                    })
                    ->max() ?? (100 + $idx);

                $nextSeq = str_pad($lastSeq + $idx + 1, 4, '0', STR_PAD_LEFT);
                $newNop = "7472{$kec}{$kel}001{$nextSeq}0";

                $item->update([
                    'generated_nop' => $newNop,
                    'status' => 'APPROVED',
                ]);
            }
        } elseif ($mutation->mutation_type === 'TRANSFER_OWNERSHIP') {
            // Skenario 5: Balik nama pemilik pada TaxObject atau TaxpayerPbbObject
            if ($mutation->parent_nop) {
                TaxpayerPbbObject::where('nop', $mutation->parent_nop)
                    ->update([
                        'name_on_sppt' => $mutation->new_taxpayer_name ?? $mutation->applicant_name,
                    ]);
                TaxObject::where('nop', $mutation->parent_nop)
                    ->update([
                        'name' => $mutation->new_taxpayer_name ?? $mutation->applicant_name,
                    ]);
            }
        } elseif ($mutation->mutation_type === 'AMALGAMATION') {
            // Skenario 6: Tandai NOP sumber sebagai non-aktif / lebur
            $sources = $mutation->merged_source_nops_json ?? [];
            if (!empty($sources)) {
                TaxObject::whereIn('nop', $sources)->update(['status' => 'MERGED']);
            }
        } elseif ($mutation->mutation_type === 'CANCELLATION') {
            // Skenario 7: Inaktivasi NOP target
            if ($mutation->duplicate_nop_target) {
                TaxObject::where('nop', $mutation->duplicate_nop_target)->update(['status' => 'CANCELLED']);
            }
        }
    }

    /**
     * Webhook Callback: Auto-trigger Balik Nama PBB saat BPHTB lunas
     */
    public function bphtbPaidCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nop' => 'required|string',
            'ntpd' => 'required|string',
            'new_owner_nik' => 'required|string|size:16',
            'new_owner_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ticketNo = 'MUT-AUTO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $mutation = PbbMutation::create([
            'ticket_no' => $ticketNo,
            'mutation_type' => 'TRANSFER_OWNERSHIP',
            'applicant_nik' => $request->new_owner_nik,
            'applicant_name' => $request->new_owner_name,
            'parent_nop' => $request->nop,
            'new_taxpayer_nik' => $request->new_owner_nik,
            'new_taxpayer_name' => $request->new_owner_name,
            'bphtb_ntpd_ref' => $request->ntpd,
            'status' => 'APPROVED', // Auto-approved via NTPD sah
            'notes' => 'Mutasi balik nama otomatis dipicu oleh pelunasan e-BPHTB NTPD: ' . $request->ntpd,
            'approved_l3_at' => now(),
        ]);

        $this->executeScenarioApproval($mutation);

        return response()->json([
            'success' => true,
            'message' => 'Mutasi PBB otomatis via e-BPHTB berhasil diproses.',
            'data' => $mutation,
        ]);
    }
}

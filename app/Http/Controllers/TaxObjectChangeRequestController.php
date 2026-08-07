<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\TaxObject;
use App\Models\TaxObjectChangeRequest;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxObjectChangeRequestController extends Controller
{
    /**
     * Field yang BOLEH diubah lewat change request.
     * NOP dan pemilik (taxpayer_id) TIDAK bisa diubah di sini - itu jalur mutasi.
     * luas_bangunan / luas_tanah disimpan ke kolom metadata objek pajak.
     */
    protected $allowedFields = [
        'name',
        'address',
        'district',
        'sub_district',
        'retribution_classification_id', // klasifikasi
        'luas_bangunan',
        'luas_tanah',
    ];

    /**
     * Field yang eksplisit DILARANG (identitas objek / kepemilikan / data sensitif).
     */
    protected $forbiddenFields = [
        'nop',
        'taxpayer_id',
        'retribution_type_id',
        'opd_id',
        'zone_id',
        'status',
        'is_active',
        'audit_status',
        'approved_by',
    ];

    /**
     * Status tagihan yang dianggap BELUM LUNAS.
     */
    protected $unpaidBillStatuses = ['pending', 'unpaid', 'overdue'];

    /**
     * Citizen: ajukan perubahan data unit pajak.
     * POST /citizen/tax-objects/{taxObject}/change-request
     */
    public function store(Request $request, TaxObject $taxObject)
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint ini hanya untuk wajib pajak.');

        // Hanya pemilik objek yang boleh mengajukan
        abort_unless((int) $taxObject->taxpayer_id === (int) $user->id, 403, 'Anda bukan pemilik unit pajak ini.');

        $validator = Validator::make($request->all(), [
            'changes' => 'required|array|min:1',
            'documents' => 'nullable|array',
            'documents.*' => 'string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $changes = $request->input('changes', []);

        // Nilai perubahan harus scalar (string/number/bool) - bukan array/objek
        foreach ($changes as $field => $value) {
            if (!is_scalar($value) && $value !== null) {
                return response()->json([
                    'message' => "Nilai perubahan untuk field '{$field}' harus berupa teks/angka.",
                    'errors' => ['changes' => [$field => 'Nilai harus scalar (string/angka/bool).']],
                ], 422);
            }
        }

        $changes = array_filter($changes, fn ($v) => $v !== null && $v !== '');

        if (empty($changes)) {
            return response()->json([
                'message' => 'Tidak ada perubahan yang diajukan. Kirim minimal satu field pada "changes".',
            ], 422);
        }

        // Blokir field terlarang (NOP / pemilik / dll)
        $forbidden = array_intersect(array_keys($changes), $this->forbiddenFields);
        if (!empty($forbidden)) {
            return response()->json([
                'message' => 'Field tidak dapat diubah lewat pengajuan ini: ' . implode(', ', $forbidden)
                    . '. Perubahan NOP/kepemilikan harus melalui jalur mutasi.',
                'errors' => ['changes' => $forbidden],
            ], 422);
        }

        // Blokir field di luar whitelist
        $unknown = array_diff(array_keys($changes), $this->allowedFields);
        if (!empty($unknown)) {
            return response()->json([
                'message' => 'Field tidak dikenali: ' . implode(', ', $unknown)
                    . '. Field yang dapat diubah: ' . implode(', ', $this->allowedFields) . '.',
                'errors' => ['changes' => array_values($unknown)],
            ], 422);
        }

        // Cegah spam: satu objek tidak boleh punya >1 pengajuan pending
        $hasPending = TaxObjectChangeRequest::where('tax_object_id', $taxObject->id)
            ->where('status', TaxObjectChangeRequest::STATUS_PENDING)
            ->exists();
        if ($hasPending) {
            return response()->json([
                'message' => 'Masih ada pengajuan perubahan yang menunggu review untuk unit pajak ini. Silakan tunggu hasil review sebelumnya.',
            ], 422);
        }

        // Bangun diff old -> new per field
        $metadata = is_string($taxObject->metadata) ? json_decode($taxObject->metadata, true) : ($taxObject->metadata ?? []);
        $metadata = is_array($metadata) ? $metadata : [];
        $fieldChanges = [];

        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['luas_bangunan', 'luas_tanah'])) {
                $metaKey = $field === 'luas_tanah' ? 'luas_bumi' : 'luas_bangunan';
                $oldValue = $metadata[$metaKey] ?? $metadata[$field] ?? null;
            } else {
                $oldValue = $taxObject->getAttribute($field);
            }

            $fieldChanges[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        try {
            $changeRequest = TaxObjectChangeRequest::create([
                'tax_object_id' => $taxObject->id,
                'taxpayer_id' => $user->id,
                'field_changes' => $fieldChanges,
                'documents' => $request->input('documents', []),
                'status' => TaxObjectChangeRequest::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Pengajuan belum berhasil dikirim. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'message' => 'Pengajuan perubahan data berhasil dikirim dan menunggu review Bapenda.',
            'data' => $changeRequest->load('taxObject:id,nop,name'),
        ], 201);
    }

    /**
     * Citizen: pantau status pengajuan sendiri.
     * GET /citizen/change-requests
     */
    public function citizenIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint ini hanya untuk wajib pajak.');

        $query = TaxObjectChangeRequest::query()
            ->with('taxObject:id,nop,name,address')
            ->where('taxpayer_id', $user->id);

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->latest()->paginate((int) $request->get('per_page', 15))
        );
    }

    /**
     * Admin: antrian pengajuan perubahan data.
     * GET /change-requests
     */
    public function index(Request $request)
    {
        $query = TaxObjectChangeRequest::query()
            ->with([
                'taxObject:id,nop,name,address,retribution_classification_id',
                'taxpayer:id,name,nik,npwpd',
                'reviewer:id,name',
            ]);

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('tax_object_id')) {
            $query->where('tax_object_id', $request->tax_object_id);
        }

        // Antrian pending tampil paling atas
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest('id');

        return response()->json(
            $query->paginate((int) $request->get('per_page', 15))
        );
    }

    /**
     * Admin: approve / reject pengajuan.
     * PUT /change-requests/{changeRequest}/status
     *
     * Saat approve: field_changes diterapkan ke tax_objects (audit trail otomatis
     * via trait Auditable) dan status pengajuan menjadi approved.
     * Saat reject: alasan disimpan ke review_notes.
     * Sebelum approve: wajib pajak tidak boleh memiliki tagihan belum lunas.
     */
    public function updateStatus(Request $request, TaxObjectChangeRequest $changeRequest)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        if ($changeRequest->status !== TaxObjectChangeRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Pengajuan ini sudah diproses sebelumnya (status: ' . $changeRequest->status . ').',
            ], 422);
        }

        $reviewer = $request->user();
        $status = $request->status;
        $notes = $request->input('review_notes');

        // --- APPROVE ---
        if ($status === 'approved') {
            // Aturan bayar: wajib pajak harus lunas tagihan dulu
            $hasUnpaidBill = Bill::where('taxpayer_id', $changeRequest->taxpayer_id)
                ->whereIn('status', $this->unpaidBillStatuses)
                ->exists();

            if ($hasUnpaidBill) {
                $reason = 'wajib lunas tagihan';
                $changeRequest->update([
                    'status' => TaxObjectChangeRequest::STATUS_REJECTED,
                    'reviewer_id' => $reviewer->id,
                    'review_notes' => trim(($notes ? $notes . ' — ' : '') . $reason),
                    'reviewed_at' => Carbon::now(),
                ]);

                return response()->json([
                    'message' => 'Pengajuan ditolak: wajib pajak masih memiliki tagihan belum lunas. Selesaikan seluruh tagihan terlebih dahulu, lalu ajukan ulang.',
                    'data' => $changeRequest->fresh()->load('taxObject:id,nop,name'),
                ], 422);
            }

            // Terapkan field_changes ke tax_objects
            $fieldChanges = $changeRequest->field_changes ?? [];
            $taxObject = $changeRequest->taxObject;

            if (empty($fieldChanges) || !$taxObject) {
                return response()->json([
                    'message' => 'Data pengajuan tidak valid (field_changes kosong atau objek tidak ditemukan).',
                ], 422);
            }

            // Validasi klasifikasi jika diubah
            if (array_key_exists('retribution_classification_id', $fieldChanges)) {
                $newKls = $fieldChanges['retribution_classification_id']['new'] ?? null;
                if ($newKls !== null && !\App\Models\RetributionClassification::whereKey($newKls)->exists()) {
                    $changeRequest->update([
                        'status' => TaxObjectChangeRequest::STATUS_REJECTED,
                        'reviewer_id' => $reviewer->id,
                        'review_notes' => trim(($notes ? $notes . ' — ' : '') . 'klasifikasi tidak valid'),
                        'reviewed_at' => Carbon::now(),
                    ]);

                    return response()->json([
                        'message' => 'Pengajuan ditolak: klasifikasi yang diajukan tidak valid.',
                        'data' => $changeRequest->fresh()->load('taxObject:id,nop,name'),
                    ], 422);
                }
            }

            $updateData = [];
            $metadata = is_string($taxObject->metadata) ? json_decode($taxObject->metadata, true) : ($taxObject->metadata ?? []);
            $metadata = is_array($metadata) ? $metadata : [];
            $metadataChanged = false;

            foreach ($fieldChanges as $field => $diff) {
                $newValue = $diff['new'] ?? null;

                if (in_array($field, ['luas_bangunan', 'luas_tanah'])) {
                    $metaKey = $field === 'luas_tanah' ? 'luas_bumi' : 'luas_bangunan';
                    $metadata[$metaKey] = $newValue;
                    $metadataChanged = true;
                    continue;
                }

                $updateData[$field] = $newValue;
            }

            if ($metadataChanged) {
                $updateData['metadata'] = $metadata;
            }

            try {
                $taxObject->update($updateData); // trait Auditable -> audit trail otomatis
                $changeRequest->update([
                    'status' => TaxObjectChangeRequest::STATUS_APPROVED,
                    'reviewer_id' => $reviewer->id,
                    'review_notes' => $notes,
                    'reviewed_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
                return response()->json([
                    'message' => 'Perubahan gagal diterapkan. Silakan coba lagi.',
                ], 500);
            }

            return response()->json([
                'message' => 'Pengajuan disetujui. Perubahan data telah diterapkan ke unit pajak.',
                'data' => $changeRequest->fresh()->load('taxObject:id,nop,name', 'reviewer:id,name'),
            ], 200);
        }

        // --- REJECT ---
        $changeRequest->update([
            'status' => TaxObjectChangeRequest::STATUS_REJECTED,
            'reviewer_id' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Pengajuan ditolak. Alasan telah dicatat oleh admin.',
            'data' => $changeRequest->fresh()->load('taxObject:id,nop,name', 'reviewer:id,name'),
        ], 200);
    }
}

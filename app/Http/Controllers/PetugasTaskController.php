<?php

namespace App\Http\Controllers;

use App\Models\PetugasTask;
use App\Models\Scopes\RetributionTypeScope;
use App\Models\TaxObject;
use App\Models\User;
use App\Models\Verification;
use App\Services\WaGatewayService;
use App\Support\TaxDomainHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PetugasTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PetugasTask::with(['user', 'zone', 'taxpayer', 'taxObject.classification', 'verification', 'creator']);

        // Jika dia role petugas, hanya lihat tugasnya sendiri
        if ($user->role === 'petugas') {
            $query->where('user_id', $user->id);
        } elseif (! $user->isSuperAdmin()) {
            // Jika bukan super_admin/admin, hanya lihat tugas di OPD-nya
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('opd_id', $user->opd_id);
            });
        }

        // Isolasi Wilayah I / Wilayah II — sebatas domain pajak user.
        // Tugas penyidikan/penagihan lapangan tidak selalu terikat objek pajak
        // (71/71 baris punya tax_object_id NULL). Tugas tanpa objek TIDAK bisa
        // dipetakan ke wilayah, jadi dibiarkan tampil — menyaringnya
        // berdasarkan relasi akan mengosongkan daftar tanpa alasan.
        // Yang disaring: tugas yang PUNYA objek, harus sesuai wilayah user.
        $dom = TaxDomainHelper::getDomain($user);
        if ($dom !== TaxDomainHelper::DOMAIN_ALL) {
            $clsIds = TaxDomainHelper::getClassificationIds($dom);
            if (! empty($clsIds)) {
                $query->where(function ($q) use ($clsIds) {
                    $q->whereNull('tax_object_id')
                        ->orWhereHas('taxObject', fn ($t) => $t->withoutGlobalScopes()
                            ->whereIn('retribution_classification_id', $clsIds))
                        ->orWhereHas('verification.taxObject', fn ($t) => $t->withoutGlobalScopes()
                            ->whereIn('retribution_classification_id', $clsIds));
                });
            }
        }

        // Filter status all, pending, completed
        if ($request->has('status') && in_array($request->status, ['pending', 'completed'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('task_type')) {
            $query->where('task_type', $request->task_type);
        }

        if ($request->has('tax_object_id')) {
            $query->where('tax_object_id', $request->tax_object_id);
        }

        if ($request->has('verification_id')) {
            $query->where('verification_id', $request->verification_id);
        }

        if ($request->has('verification_status')) {
            if ($request->verification_status === 'verified') {
                $query->where(function ($q) {
                    $q->whereHas('verification', function ($vq) {
                        $vq->where('status', 'approved');
                    })->orWhere(function ($oq) {
                        $oq->whereNull('verification_id')
                            ->whereHas('taxObject', function ($toq) {
                                $toq->where('is_verified_physically', true);
                            });
                    });
                });
            } elseif ($request->verification_status === 'unverified') {
                $query->where(function ($q) {
                    $q->whereHas('verification', function ($vq) {
                        $vq->whereIn('status', ['pending', 'in_review']);
                    })->orWhere(function ($oq) {
                        $oq->whereNull('verification_id')
                            ->where(function ($noVerif) {
                                $noVerif->whereDoesntHave('taxObject')
                                    ->orWhereHas('taxObject', function ($toq) {
                                        $toq->where('is_verified_physically', false);
                                    });
                            });
                    });
                });
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('due_date', 'asc')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isPengawas() && $user->role !== 'opd') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'taxpayer_id' => 'nullable|exists:taxpayers,id',
            'tax_object_id' => 'nullable|exists:tax_objects,id',
            'verification_id' => 'nullable|exists:verifications,id',
            'task_type' => 'nullable|string|in:general,field_survey,collection,validation',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $targetUser = User::withoutGlobalScope(RetributionTypeScope::class)->find($request->user_id);

        if (! $targetUser) {
            return response()->json(['message' => 'Petugas tidak ditemukan di dalam sistem.'], 404);
        }

        if ($targetUser->role !== 'petugas') {
            return response()->json(['message' => 'User yang dipilih bukan petugas lapangan.'], 422);
        }

        // Allow cross-OPD technical assignment between Bapenda (5), DLH (4), PUPR (9), and PERKIM (11) for field surveys / MBLB
        $isTechnicalCrossOpd = in_array((int) $user->opd_id, [4, 5, 9, 11]) && in_array((int) $targetUser->opd_id, [4, 5, 9, 11]);

        if (! $user->isSuperAdmin() && $targetUser->opd_id !== $user->opd_id && ! $isTechnicalCrossOpd) {
            return response()->json(['message' => 'Akses Ditolak: Anda hanya bisa menugaskan petugas pada OPD Anda sendiri.'], 403);
        }

        if ($user->role === 'admin' && $user->retribution_type_id && $targetUser->retribution_type_id !== $user->retribution_type_id && ! $isTechnicalCrossOpd) {
            return response()->json(['message' => 'Akses Ditolak: Anda hanya bisa menugaskan petugas pada Tipe Pajak/Wilayah Anda sendiri.'], 403);
        }

        $validated['task_type'] = $validated['task_type'] ?? 'general';

        if (! empty($validated['tax_object_id'])) {
            $taxObject = TaxObject::withoutGlobalScopes()
                ->with('taxpayer')
                ->find($validated['tax_object_id']);

            if (! $taxObject) {
                return response()->json(['message' => 'Objek pajak/retribusi tidak ditemukan.'], 404);
            }

            if (! $user->isSuperAdmin() && (int) $taxObject->opd_id !== (int) $user->opd_id && ! $isTechnicalCrossOpd) {
                return response()->json(['message' => 'Akses Ditolak: Objek berada di OPD lain.'], 403);
            }

            $validated['taxpayer_id'] = $taxObject->taxpayer_id;
        }

        if (! empty($validated['verification_id'])) {
            $verification = Verification::with('taxObject')->find($validated['verification_id']);

            if (! $verification) {
                return response()->json(['message' => 'Dokumen verifikasi tidak ditemukan.'], 404);
            }

            if (! $user->isSuperAdmin() && (int) $verification->opd_id !== (int) $user->opd_id && ! $isTechnicalCrossOpd) {
                return response()->json(['message' => 'Akses Ditolak: Verifikasi berada di OPD lain.'], 403);
            }

            if (! empty($validated['tax_object_id']) && (int) $verification->tax_object_id !== (int) $validated['tax_object_id']) {
                return response()->json(['message' => 'Dokumen verifikasi tidak sesuai dengan objek yang dipilih.'], 422);
            }

            if (in_array($verification->status, ['approved', 'rejected'], true)) {
                return response()->json(['message' => 'Survey tidak dapat ditugaskan untuk verifikasi yang sudah final.'], 422);
            }
        }

        if ($validated['task_type'] === 'field_survey' && ! empty($validated['tax_object_id'])) {
            $duplicateQuery = PetugasTask::where('task_type', 'field_survey')
                ->where('tax_object_id', $validated['tax_object_id'])
                ->where('status', 'pending');

            if (! empty($validated['verification_id'])) {
                $duplicateQuery->where('verification_id', $validated['verification_id']);
            }

            if ($duplicateQuery->exists()) {
                return response()->json(['message' => 'Masih ada tugas survey lapangan yang pending untuk objek ini.'], 422);
            }
        }

        $validated['created_by'] = $user->id;
        $validated['status'] = 'pending';

        $task = PetugasTask::create($validated);

        if (! empty($validated['verification_id'])) {
            Verification::whereKey($validated['verification_id'])
                ->whereIn('status', ['pending', 'in_review'])
                ->update(['status' => 'in_review']);
        }

        // Sinkronisasi status penugasan faktual ke metadata TaxObject
        if (! empty($validated['tax_object_id'])) {
            $obj = TaxObject::withoutGlobalScopes()->find($validated['tax_object_id']);
            if ($obj) {
                $meta = $obj->metadata ?? [];
                $meta['pending_field_survey'] = true;
                $meta['assigned_surveyor'] = [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'opd' => $targetUser->opd?->name ?? 'Dinas Terkait',
                    'opd_code' => $targetUser->opd?->code ?? '',
                    'due_date' => $validated['due_date'],
                    'notes' => $validated['notes'] ?? 'Verifikasi faktual lapangan kegiatan usaha/galian MBLB.',
                ];
                $obj->update(['metadata' => $meta]);
            }
        }

        // Kirim notifikasi penugasan WhatsApp ke nomor HP petugas bila ada
        if (! empty($targetUser->phone)) {
            try {
                $waGateway = app(WaGatewayService::class);
                $opdName = $targetUser->opd?->name ?? 'Dinas Terkait';
                $objName = $task->taxObject?->name ?? 'Objek Pajak / Giat MBLB';
                $objAddr = $task->taxObject?->address ?? 'Kota Baubau';
                $dueFormatted = Carbon::parse($validated['due_date'])->translatedFormat('d M Y');
                $instruksi = $validated['notes'] ?? 'Verifikasi faktual lapangan atas kegiatan MBLB/objek pajak';

                $waMsg = "📋 *PENUGASAN VERIFIKASI FAKTUAL LAPANGAN*\n"
                       ."Kepada: *{$targetUser->name}* ({$opdName})\n"
                       ."━━━━━━━━━━━━━━━━━━━━━━━━\n"
                       ."Anda menerima penugasan verifikasi faktual lapangan baru:\n"
                       ."🏷️ *Objek*: {$objName}\n"
                       ."📍 *Lokasi*: {$objAddr}\n"
                       ."📅 *Tenggat*: {$dueFormatted}\n"
                       ."📝 *Instruksi*: {$instruksi}\n\n"
                       ."Silakan buka aplikasi *Petugas M-PAD* untuk meninjau titik koordinat SmartGIS, mengunggah foto fisik lapangan, dan melaporkan hasil survei.\n\n"
                       .'_Sistem M-PAD Pemerintah Kota Baubau_';

                $waGateway->sendMessage($targetUser->phone, $waMsg);
            } catch (\Throwable $e) {
                Log::warning('Failed sending WA to assigned officer: '.$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil dibuat',
            'data' => $task->load(['user', 'zone', 'taxpayer', 'taxObject.classification', 'verification', 'creator']),
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $task = PetugasTask::find($id);
        if (! $task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $user = Auth::user();

        // Hanya petugas bersangkutan atau admin yang boleh update
        if ($user->role === 'petugas' && $task->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to update this task'], 403);
        }

        if ($user->role !== 'petugas' && ! $user->isSuperAdmin() && $task->user?->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized to update task from other OPD'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,completed',
            'notes' => 'nullable|string',
            'late_reason' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($validated['status'] === 'completed' && $task->status === 'pending') {
            $validated['completed_at'] = now();
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('tasks/photos', 'public');
                $validated['completion_photo_path'] = $path;
            }
        } elseif ($validated['status'] === 'pending') {
            $validated['completed_at'] = null;
            $validated['completion_photo_path'] = null;
        }

        $task->update($validated);

        if ($task->task_type === 'field_survey' && $task->status === 'completed' && $task->tax_object_id) {
            $objectUpdates = ['is_verified_physically' => true];

            if ($task->completion_photo_path) {
                $objectUpdates['last_photo_url'] = $task->completion_photo_path;
            }

            TaxObject::withoutGlobalScopes()
                ->whereKey($task->tax_object_id)
                ->update($objectUpdates);
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil diupdate',
            'data' => $task->load(['user', 'zone', 'taxpayer', 'taxObject.classification', 'verification', 'creator']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = PetugasTask::find($id);
        if (! $task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isPengawas() && $user->role !== 'opd') {
            return response()->json(['message' => 'Unauthorized deletion'], 403);
        }

        // Enforce OPD scoping for non-super-admins
        if (! $user->isSuperAdmin() && $task->user->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized deletion of other OPD tasks'], 403);
        }

        $task->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas dihapus',
        ]);
    }
}

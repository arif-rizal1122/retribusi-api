<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Models\PetugasTask;
use App\Services\CloudinaryService;
use App\Services\RegistrationVerificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VerificationController extends Controller
{
    protected $cloudinary;
    protected $registrationVerificationService;

    public function __construct(
        CloudinaryService $cloudinary,
        RegistrationVerificationService $registrationVerificationService
    )
    {
        $this->cloudinary = $cloudinary;
        $this->registrationVerificationService = $registrationVerificationService;
    }

    /**
     * Store a new verification request with proof file
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'opd_id' => 'required|exists:opds,id',
                'taxpayer_name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'proof_file' => 'required|file|image|max:5120', // Max 5MB image
            ]);

            $proofFileUrl = $this->cloudinary->upload($request->file('proof_file'), 'verifications');

            $opdId = $request->opd_id;
            if (!$user->isSuperAdmin()) {
                $opdId = $user->opd_id;
            }

            $verification = Verification::create([
                'opd_id' => $opdId,
                'user_id' => $user->id,
                'document_number' => 'VRC-' . strtoupper(uniqid()),
                'taxpayer_name' => $request->taxpayer_name,
                'type' => $request->type,
                'amount' => $request->amount,
                'proof_file_url' => $proofFileUrl,
                'status' => 'pending',
                'submitted_at' => Carbon::now(),
            ]);

            return response()->json([
                'message' => 'Permintaan verifikasi berhasil dikirim',
                'data' => $verification->load(['opd', 'submitter'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Verification Store Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mengirim verifikasi: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * List verifications (OPD-scoped)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Verification::with(['opd', 'submitter', 'verifier', 'taxObject.classification', 'taxObject.taxpayer']);
        $targetOpdId = null;

        // 1. Jika request eksplisit mengirimkan opd_id (dari URL/dropdown OPD)
        if ($request->filled('opd_id') && is_numeric($request->opd_id)) {
            $requestedOpdId = (int) $request->opd_id;
            if ($user->role === 'super_admin' || $user->role === 'admin' || !$user->opd_id || (int) $user->opd_id === $requestedOpdId) {
                $targetOpdId = $requestedOpdId;
            } else {
                $targetOpdId = (int) $user->opd_id;
            }
        } elseif ($user->role === 'opd' || (!in_array($user->role, ['super_admin']) && $user->opd_id && (int) $user->opd_id !== 5)) {
            // Pengguna OPD teknis (Perikanan, Dishub, DLH, PUPR, Pariwisata, dll) terisolasi ke OPD-nya
            $targetOpdId = (int) $user->opd_id;
        } elseif ($user->opd_id && !in_array($user->role, ['super_admin'])) {
            $targetOpdId = (int) $user->opd_id;
        }

        if ($targetOpdId) {
            $query->where(function ($q) use ($targetOpdId) {
                $q->where('opd_id', $targetOpdId)
                  ->orWhereHas('taxObject', function ($to) use ($targetOpdId) {
                      $to->withoutGlobalScopes()->where('opd_id', $targetOpdId);
                  });
            });
        }

        // Isolasi Wilayah I / Wilayah II — data hanya sebatas domain user.
        \App\Support\TaxDomainHelper::applyDomainScope($query);

        if ($request->has('status')) {
            if (is_array($request->status)) {
                $query->whereIn('status', $request->status);
            } elseif (str_contains($request->status, ',')) {
                $query->whereIn('status', explode(',', $request->status));
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('taxpayer_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('classification') && $request->classification !== 'all') {
            $classification = $request->classification;
            $query->where(function ($q) use ($classification) {
                $q->whereHas('taxObject.classification', function ($sq) use ($classification) {
                    $sq->where('name', 'like', "%{$classification}%");
                })->orWhere('type', 'like', "%{$classification}%");
            });
        }

        $verifications = $query->latest('submitted_at')->paginate($request->get('per_page', 15));
        $this->attachVerificationTimeline($verifications->getCollection());

        return response()->json($verifications);
    }

    /**
     * Update verification status (Approve/Reject)
     */
    public function updateStatus(Request $request, Verification $verification)
    {
        try {
            $user = $request->user();

            // Authority check
            if (!$user->isSuperAdmin() && $verification->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'status' => 'required|in:approved,rejected,in_review',
                'notes' => 'required_if:status,rejected|nullable|string',
            ]);

            $updatedVerification = $this->registrationVerificationService->updateStatus(
                $verification,
                $request->status,
                $request->notes,
                $user
            );
            $this->attachVerificationTimeline(collect([$updatedVerification]));

            return response()->json([
                'message' => "Dokumen berhasil di-{$request->status}",
                'data' => $updatedVerification
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Verification Status Update Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal update status verifikasi: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Show verification details
     */
    public function show(Request $request, Verification $verification)
    {
        $user = $request->user();
        
        if (!$user->isSuperAdmin() && $verification->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $verification->load(['opd', 'submitter', 'verifier', 'taxObject.classification', 'taxObject.taxpayer', 'taxpayer']);
        $this->attachVerificationTimeline(collect([$verification]));

        // ═══ STATE GUARD INFO: Kirim status pagar validasi ke UI ═══
        $stateGuard = $this->buildStateGuardInfo($verification);

        return response()->json([
            'data' => $verification,
            'state_guard' => $stateGuard,
        ]);
    }

    /**
     * Build state guard information for the admin UI.
     * Menentukan apakah tombol "Setujui" boleh aktif berdasarkan aturan bisnis.
     */
    private function buildStateGuardInfo(Verification $verification): array
    {
        $guard = [
            'can_approve' => true,
            'requires_field_survey' => false,
            'approval_method' => 'document_review',
            'field_check_params' => [],
            'survey_status' => null,
            'survey_task' => null,
            'block_reasons' => [],
        ];

        if (!$verification->tax_object_id || !$verification->taxObject || !$verification->taxObject->classification) {
            return $guard;
        }

        $classification = $verification->taxObject->classification;
        $guard['requires_field_survey'] = $classification->requiresFieldSurvey();
        $guard['approval_method'] = $classification->approval_method ?? 'field_survey';
        $guard['field_check_params'] = $classification->field_check_params ?? [];

        if (!$classification->requiresFieldSurvey()) {
            return $guard; // Tidak wajib survei → langsung boleh approve
        }

        // Cek penugasan field_survey
        $latestTask = PetugasTask::where('tax_object_id', $verification->tax_object_id)
            ->where('task_type', 'field_survey')
            ->with('user:id,name')
            ->latest('created_at')
            ->first();

        if (!$latestTask) {
            $guard['can_approve'] = false;
            $guard['survey_status'] = 'no_assignment';
            $guard['block_reasons'][] = 'Wajib menugaskan petugas lapangan terlebih dahulu.';
            return $guard;
        }

        $guard['survey_task'] = [
            'id' => $latestTask->id,
            'status' => $latestTask->status,
            'officer_name' => $latestTask->user?->name,
            'due_date' => $latestTask->due_date,
            'completed_at' => $latestTask->completed_at,
            'has_photo' => !empty($latestTask->completion_photo_path),
            'notes' => $latestTask->notes,
        ];

        if ($latestTask->status !== 'completed') {
            $guard['can_approve'] = false;
            $guard['survey_status'] = 'in_progress';
            $guard['block_reasons'][] = 'Penugasan survei lapangan belum selesai. Tunggu petugas menyelesaikan laporan.';
            return $guard;
        }

        if (empty($latestTask->completion_photo_path)) {
            $guard['can_approve'] = false;
            $guard['survey_status'] = 'incomplete_report';
            $guard['block_reasons'][] = 'Laporan lapangan belum memuat foto bukti fisik.';
            return $guard;
        }

        $guard['survey_status'] = 'completed';
        $guard['can_approve'] = true;

        return $guard;
    }

    private function attachVerificationTimeline($verifications): void
    {
        $objectIds = $verifications
            ->pluck('tax_object_id')
            ->filter()
            ->unique()
            ->values();

        if ($objectIds->isEmpty()) {
            return;
        }

        $timelineByObject = Verification::whereIn('tax_object_id', $objectIds)
            ->with('verifier:id,name')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('tax_object_id');

        $surveyTasksByObject = PetugasTask::whereIn('tax_object_id', $objectIds)
            ->where('task_type', 'field_survey')
            ->with(['user:id,name', 'creator:id,name'])
            ->orderBy('created_at')
            ->get()
            ->groupBy('tax_object_id');

        $verifications->each(function (Verification $verification) use ($timelineByObject, $surveyTasksByObject) {
            if (!$verification->taxObject) {
                return;
            }

            // Fallback kalkulasi estimasi jika amount masih 0
            if ((float) $verification->amount <= 0) {
                try {
                    $calcService = app(\App\Services\TaxCalculationService::class);
                    $estimated = (float) $calcService->calculateAmount($verification->taxObject);
                    if ($estimated > 0) {
                        $verification->amount = $estimated;
                    }
                } catch (\Throwable $e) {
                    // Abaikan jika objek belum memiliki formula yang valid
                }
            }

            $timeline = ($timelineByObject->get($verification->tax_object_id) ?? collect())
                ->map(fn (Verification $item) => $this->formatVerificationTimelineItem($item))
                ->values();

            $verification->taxObject->setAttribute('verification_timeline', $timeline);

            $surveyTasks = ($surveyTasksByObject->get($verification->tax_object_id) ?? collect())
                ->map(fn (PetugasTask $task) => $this->formatFieldSurveyTask($task))
                ->values();

            $verification->taxObject->setAttribute('field_survey_tasks', $surveyTasks);
            $verification->taxObject->setAttribute('latest_field_survey_task', $surveyTasks->last());
            $verification->setAttribute('state_guard', $this->buildStateGuardInfo($verification));
        });
    }

    private function formatVerificationTimelineItem(Verification $verification): array
    {
        return [
            'id' => $verification->id,
            'document_number' => $verification->document_number,
            'type' => $verification->type,
            'status' => $verification->status,
            'notes' => $verification->notes,
            'submitted_at' => $verification->submitted_at,
            'verified_at' => $verification->verified_at,
            'verifier_name' => $verification->verifier?->name,
        ];
    }

    private function formatFieldSurveyTask(PetugasTask $task): array
    {
        $photoUrl = null;
        if ($task->completion_photo_path) {
            $photoUrl = str_starts_with($task->completion_photo_path, 'http')
                ? $task->completion_photo_path
                : url('storage/' . ltrim($task->completion_photo_path, '/'));
        }

        return [
            'id' => $task->id,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'notes' => $task->notes,
            'completed_at' => $task->completed_at,
            'officer_name' => $task->user?->name,
            'created_by_name' => $task->creator?->name,
            'completion_photo_path' => $photoUrl ?? $task->completion_photo_path,
            'completion_photo_url' => $photoUrl,
        ];
    }
}

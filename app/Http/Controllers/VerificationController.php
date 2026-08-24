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

        if (!$user->isSuperAdmin() && $user->opd_id) {
            $query->where('opd_id', $user->opd_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
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
                'notes' => 'required_if:status,approved,rejected|nullable|string',
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

        return response()->json([
            'data' => $verification
        ]);
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

            $timeline = ($timelineByObject->get($verification->tax_object_id) ?? collect())
                ->map(fn (Verification $item) => $this->formatVerificationTimelineItem($item))
                ->values();

            $verification->taxObject->setAttribute('verification_timeline', $timeline);

            $surveyTasks = ($surveyTasksByObject->get($verification->tax_object_id) ?? collect())
                ->map(fn (PetugasTask $task) => $this->formatFieldSurveyTask($task))
                ->values();

            $verification->taxObject->setAttribute('field_survey_tasks', $surveyTasks);
            $verification->taxObject->setAttribute('latest_field_survey_task', $surveyTasks->last());
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
        return [
            'id' => $task->id,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'notes' => $task->notes,
            'completed_at' => $task->completed_at,
            'officer_name' => $task->user?->name,
            'created_by_name' => $task->creator?->name,
            'completion_photo_path' => $task->completion_photo_path,
        ];
    }
}

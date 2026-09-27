<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceApplication;
use App\Models\ServiceApplicationLog;
use App\Models\User;
use App\Services\BusinessDayCalculator;
use App\Services\ServiceApplicationNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Modul Permohonan Layanan generik (Reklame 188, PBG 193, Air Tanah 197, TKA 194).
 *
 * SLA: 2 hari kerja total (Senin-Jumat, 08.00-16.00 WITA), melewati Sabtu/Minggu/libur.
 *   Tahap 1 - Verifikasi & Disposisi PTSP : 1 hari kerja
 *   Tahap 2 - Cek Fisik Lapangan          : 1 hari kerja
 */
class ServiceApplicationController extends Controller
{
    public const TOTAL_WORK_DAYS = 2;

    public function __construct(
        protected ServiceApplicationNotifier $notifier
    ) {}

    /** Konfigurasi per jenis layanan. */
    protected function serviceConfig(string $code): array
    {
        $map = [
            '188_PAJAK_REKLAME' => ['nama' => 'Pajak Reklame', 'prefix' => 'REK'],
            '193_PBG'           => ['nama' => 'Persetujuan Bangunan Gedung', 'prefix' => 'PBG'],
            '197_AIR_TANAH'     => ['nama' => 'Pajak Air Tanah', 'prefix' => 'PAT'],
            '194_IZIN_TKA'      => ['nama' => 'Izin Tenaga Kerja Asing', 'prefix' => 'TKA'],
        ];
        return $map[$code] ?? ['nama' => 'Layanan', 'prefix' => 'SRV'];
    }

    /** Nomor permohonan: REQ-REK-202609-0001 */
    protected function generateApplicationNo(string $serviceCode): string
    {
        $cfg = $this->serviceConfig($serviceCode);
        $prefix = 'REQ-' . $cfg['prefix'] . '-' . now()->format('Ym') . '-';

        return DB::transaction(function () use ($prefix) {
            $last = ServiceApplication::withTrashed()
                ->where('application_no', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('application_no');

            $seq = 1;
            if ($last) {
                $seq = ((int) substr($last, -4)) + 1;
            }

            return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }

    /** Catat transisi status (audit trail). */
    protected function logStatus(
        ServiceApplication $app,
        ?string $from,
        string $to,
        ?User $actor,
        ?string $notes = null,
        array $meta = []
    ): void {
        ServiceApplicationLog::create([
            'application_id' => $app->id,
            'from_status'    => $from,
            'to_status'      => $to,
            'actor_id'       => $actor?->id,
            'actor_role'     => $actor?->role,
            'notes'          => $notes,
            'meta'           => $meta ?: null,
        ]);
    }

    // =====================================================================
    // PEMOHON
    // =====================================================================

    /**
     * POST /api/service-applications
     * Ajukan permohonan baru. Hitung SLA hari kerja, kirim WA ke pemohon + PTSP.
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'service_code'      => 'required|string|max:60',
            'payload'           => 'required|array',
            'contact_name'      => 'nullable|string|max:150',
            'contact_phone'     => 'nullable|string|max:30',
            'contact_email'     => 'nullable|email|max:150',
            'ptsp_phone'        => 'nullable|string|max:30',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = $request->user();
        $cfg = $this->serviceConfig($request->service_code);
        $now = BusinessDayCalculator::nowWita();

        // SLA agregat: 2 hari kerja dari submit
        $deadline = BusinessDayCalculator::addWorkDays($now, self::TOTAL_WORK_DAYS);

        $app = DB::transaction(function () use ($request, $user, $cfg, $deadline) {
            $app = ServiceApplication::create([
                'application_no' => $this->generateApplicationNo($request->service_code),
                'user_id'        => $user->id,
                'service_code'   => $request->service_code,
                'service_name'   => $cfg['nama'],
                'payload'        => $request->payload,
                'status'         => 'submitted',
                'contact_name'   => $request->contact_name ?? $user->name,
                'contact_phone'  => $request->contact_phone ?? $user->phone,
                'sla_deadline'   => $deadline,
            ]);

            $this->logStatus($app, null, 'submitted', $user, 'Permohonan diajukan', [
                'sla_deadline' => $deadline->toIso8601String(),
                'sla_rule'     => '2 hari kerja (Senin-Jumat 08.00-16.00 WITA)',
            ]);

            return $app;
        });

        // Notifikasi (non-blocking: gagal WA tidak membatalkan permohonan)
        $waPemohon = $this->notifier->notifySubmitted($app);
        $waPtsp = $this->notifier->notifyPtspNewApplication($app, $request->ptsp_phone);

        return response()->json([
            'success'          => true,
            'application_no'   => $app->application_no,
            'id'               => $app->id,
            'status'           => $app->status,
            'status_label'     => $app->statusLabel(),
            'sla_deadline'     => $deadline->toIso8601String(),
            'sla_deadline_id'  => BusinessDayCalculator::formatId($deadline),
            'sla_work_days'    => self::TOTAL_WORK_DAYS,
            'notified_applicant' => $waPemohon,
            'notified_ptsp'      => $waPtsp,
        ], 201);
    }

    /** GET /api/service-applications — daftar milik pemohon sendiri */
    public function mine(Request $request)
    {
        $q = ServiceApplication::with(['logs', 'officer:id,name,role'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $items = $q->orderByDesc('created_at')->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => collect($items->items())->map(fn ($a) => [
                'id'              => $a->id,
                'application_no'  => $a->application_no,
                'service_name'    => $a->service_name,
                'status'          => $a->status,
                'status_label'    => $a->statusLabel(),
                'is_sla_breached' => $a->is_sla_breached,
                'sla_deadline'    => optional($a->sla_deadline)->toIso8601String(),
                'assigned_officer' => optional($a->officer)->name,
                'created_at'      => $a->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    /** GET /api/service-applications/{id} — detail + riwayat log */
    public function show(Request $request, $id)
    {
        $app = ServiceApplication::with(['logs', 'officer:id,name,role,phone'])
            ->findOrFail($id);

        $user = $request->user();
        $isOwner = (int) $app->user_id === (int) $user->id;
        $isOfficer = in_array($user->role, ['admin_ptsp', 'petugas_lapangan', 'surveyor', 'kabid', 'admin', 'super_admin'], true);

        if (!$isOwner && !$isOfficer) {
            return response()->json(['success' => false, 'message' => 'Tidak berwenang'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $app->id,
                'application_no'   => $app->application_no,
                'service_code'     => $app->service_code,
                'service_name'     => $app->service_name,
                'status'           => $app->status,
                'status_label'     => $app->statusLabel(),
                'payload'          => $app->payload,
                'contact_name'     => $app->contact_name,
                'contact_phone'    => $app->contact_phone,
                'assigned_officer' => $app->officer ? [
                    'id' => $app->officer->id, 'name' => $app->officer->name, 'role' => $app->officer->role,
                ] : null,
                'assigned_at'      => optional($app->assigned_at)->toIso8601String(),
                'field_checked_at' => optional($app->field_checked_at)->toIso8601String(),
                'field_notes'      => $app->field_notes,
                'sla_deadline'     => optional($app->sla_deadline)->toIso8601String(),
                'sla_deadline_id'  => $app->sla_deadline ? BusinessDayCalculator::formatId($app->sla_deadline) : null,
                'is_sla_breached'  => $app->is_sla_breached,
                'permit_no'        => $app->permit_no,
                'rejection_reason' => $app->rejection_reason,
                'created_at'       => $app->created_at->toIso8601String(),
                'logs'             => $app->logs->map(fn ($l) => [
                    'from'   => $l->from_status, 'to' => $l->to_status,
                    'actor'  => $l->actor_id, 'role' => $l->actor_role,
                    'notes'  => $l->notes, 'at' => optional($l->created_at)->toIso8601String(),
                ]),
            ],
        ]);
    }

    // =====================================================================
    // ADMIN PTSP
    // =====================================================================

    /** GET /api/ptsp/service-applications — daftar masuk untuk PTSP */
    public function ptspIndex(Request $request)
    {
        $q = ServiceApplication::with(['officer:id,name,role', 'applicant:id,name,phone']);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('service_code')) {
            $q->where('service_code', $request->service_code);
        }
        if ($request->boolean('breached_only')) {
            $q->where('is_sla_breached', true);
        }

        // Urutkan: yang lewat SLA lebih dulu, lalu tenggat paling dekat
        $items = $q->orderByDesc('is_sla_breached')
            ->orderBy('sla_deadline')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => collect($items->items())->map(fn ($a) => [
                'id'              => $a->id,
                'application_no'  => $a->application_no,
                'service_name'    => $a->service_name,
                'applicant'       => optional($a->applicant)->name,
                'status'          => $a->status,
                'status_label'    => $a->statusLabel(),
                'sla_deadline'    => optional($a->sla_deadline)->toIso8601String(),
                'is_sla_breached' => $a->is_sla_breached,
                'officer'         => optional($a->officer)->name,
                'created_at'      => $a->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'total'        => $items->total(),
                'breached'     => ServiceApplication::where('is_sla_breached', true)->count(),
            ],
        ]);
    }

    /**
     * POST /api/ptsp/service-applications/{id}/assign
     * Tugaskan petugas lapangan. Kirim WA ke petugas, catat log, update SLA tahap 2.
     */
    public function assignOfficer(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'officer_id' => 'required|integer|exists:users,id',
            'notes'      => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $actor = $request->user();
        $app = ServiceApplication::findOrFail($id);

        if (in_array($app->status, ['permit_issued', 'rejected'], true)) {
            return response()->json(['success' => false, 'message' => 'Permohonan sudah final'], 409);
        }

        $officer = User::findOrFail($request->officer_id);
        $now = BusinessDayCalculator::nowWita();
        // SLA tahap 2: 1 hari kerja dari penugasan
        $fieldDeadline = BusinessDayCalculator::addWorkDays($now, 1);

        DB::transaction(function () use ($app, $officer, $actor, $request, $fieldDeadline, $now) {
            $from = $app->status;
            $app->update([
                'status'              => 'field_assigned',
                'assigned_officer_id' => $officer->id,
                'assigned_at'         => $now,
                'sla_deadline'        => $fieldDeadline,
                'is_sla_breached'     => false,
                'sla_breached_at'     => null,
            ]);

            $this->logStatus($app, $from, 'field_assigned', $actor,
                $request->notes ?? "Ditugaskan ke {$officer->name}", [
                    'officer_id'         => $officer->id,
                    'field_sla_deadline' => $fieldDeadline->toIso8601String(),
                ]);
        });

        $wa = $this->notifier->notifyFieldAssignment($app->fresh('officer'));

        return response()->json([
            'success'            => true,
            'status'             => 'field_assigned',
            'officer'            => $officer->name,
            'sla_deadline'       => $fieldDeadline->toIso8601String(),
            'sla_deadline_id'    => BusinessDayCalculator::formatId($fieldDeadline),
            'notified_officer'   => $wa,
        ]);
    }

    /**
     * POST /api/ptsp/service-applications/{id}/field-check
     * Petugas lapangan menyampaikan hasil cek fisik ruang reklame.
     */
    public function fieldCheck(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'result'      => 'required|in:AMAN,BERMASALAH',
            'notes'       => 'nullable|string|max:2000',
            'photo_urls'  => 'nullable|array',
            'lat'         => 'nullable|numeric|between:-90,90',
            'lng'         => 'nullable|numeric|between:-180,180',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $actor = $request->user();
        $app = ServiceApplication::findOrFail($id);

        if ((int) $app->assigned_officer_id !== (int) $actor->id
            && !in_array($actor->role, ['admin_ptsp', 'kabid', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Bukan petugas yang ditugaskan'], 403);
        }

        $now = BusinessDayCalculator::nowWita();

        DB::transaction(function () use ($app, $actor, $request, $now) {
            $from = $app->status;
            $app->update([
                'status'           => 'field_checked',
                'field_checked_at' => $now,
                'field_notes'      => $request->notes,
                'payload'          => array_merge($app->payload ?? [], [
                    'field_check' => [
                        'result'     => $request->result,
                        'notes'      => $request->notes,
                        'photo_urls' => $request->photo_urls ?? [],
                        'lat'        => $request->lat,
                        'lng'        => $request->lng,
                        'checked_by' => $actor->id,
                        'checked_at' => $now->toIso8601String(),
                    ],
                ]),
            ]);

            $this->logStatus($app, $from, 'field_checked', $actor,
                "Hasil cek lapangan: {$request->result}" . ($request->notes ? " — {$request->notes}" : ''), [
                    'result' => $request->result,
                ]);
        });

        return response()->json([
            'success'  => true,
            'status'   => 'field_checked',
            'result'   => $request->result,
            'next'     => 'Verifikasi akhir oleh Admin PTSP/Bapenda',
        ]);
    }

    /**
     * POST /api/ptsp/service-applications/{id}/decide
     * Keputusan akhir PTSP/Bapenda: approve / reject / revision.
     */
    public function decide(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'decision' => 'required|in:approve,reject,revision',
            'notes'    => 'nullable|string|max:2000',
            'permit_no'=> 'nullable|string|max:80',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $actor = $request->user();
        $app = ServiceApplication::findOrFail($id);

        if (in_array($app->status, ['permit_issued', 'rejected'], true)) {
            return response()->json(['success' => false, 'message' => 'Permohonan sudah final'], 409);
        }

        $newStatus = match ($request->decision) {
            'approve'  => 'billing_issued',
            'reject'   => 'rejected',
            'revision' => 'revision_needed',
        };

        DB::transaction(function () use ($app, $actor, $request, $newStatus) {
            $from = $app->status;
            $update = ['status' => $newStatus];

            if ($newStatus === 'rejected') {
                $update['rejection_reason'] = $request->notes;
                $update['completed_at']     = now();
            } elseif ($newStatus === 'billing_issued') {
                $update['permit_no'] = $request->permit_no;
            }

            $app->update($update);
            $this->logStatus($app, $from, $newStatus, $actor, $request->notes);
        });

        // Notifikasi ke pemohon
        $wa = $newStatus === 'billing_issued'
            ? $this->notifier->notifyBillingIssued($app)
            : $this->notifier->notifyStatusChange($app, $newStatus, $request->notes);

        return response()->json([
            'success'          => true,
            'status'           => $newStatus,
            'status_label'     => $app->fresh()->statusLabel(),
            'notified_applicant' => $wa,
        ]);
    }

    /** GET /api/ptsp/officers — daftar petugas lapangan untuk dropdown assign */
    public function officers(Request $request)
    {
        $q = User::query()
            ->whereIn('role', ['petugas_lapangan', 'surveyor'])
            ->select('id', 'name', 'role', 'phone', 'opd_id');

        if ($request->filled('opd_id')) {
            $q->where('opd_id', $request->opd_id);
        }

        return response()->json([
            'success' => true,
            'data'    => $q->orderBy('name')->get(),
        ]);
    }
}

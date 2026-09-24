<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AutoAssignService;
use App\Services\LapanganVerifikasiService;
use App\Services\SlaTrackerService;
use App\Support\ServiceAccessMatrix;
use App\Support\TaxDomainHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * PekerjaanLapanganController — alur SERAH-TERIMA pekerjaan lapangan.
 *
 * Memisahkan dua sisi secara tegas:
 *
 *   SISI PETUGAS  (role petugas, PIC tugas)
 *     POST /bidang-pajak/lapangan/{id}/ajukan
 *         Petugas menyatakan "sudah saya kerjakan". Status menjadi
 *         MENUNGGU_VERIFIKASI — BUKAN selesai.
 *
 *   SISI KASUBID  (gate service_action:verify)
 *     GET  /bidang-pajak/lapangan/daftar    antrian menunggu verifikasi
 *     GET  /bidang-pajak/lapangan/{id}      detail + riwayat audit
 *     POST /bidang-pajak/lapangan/{id}/verifikasi
 *     POST /bidang-pajak/lapangan/{id}/tolak
 *
 * Yang MEMASTIKAN SELESAI adalah Kasubid, bukan petugas. Ini inti
 * pemisahan maker-checker yang diminta pengelola.
 */
class PekerjaanLapanganController extends Controller
{
    public function __construct(
        private LapanganVerifikasiService $lapangan,
        private AutoAssignService $audit,
    ) {
    }

    // ==================================================================
    // SISI PETUGAS
    // ==================================================================

    /**
     * GET /api/bidang-pajak/lapangan/tugas-saya
     * Daftar tugas milik petugas yang sedang login.
     */
    public function tugasSaya(Request $request)
    {
        $user = $request->user();

        $status = $request->query('status');

        $q = DB::table('service_task_assignments as sta')
            ->select(
                'sta.id', 'sta.reference_type', 'sta.reference_id', 'sta.stage',
                'sta.tax_domain', 'sta.tax_sector_code', 'sta.status',
                'sta.sla_deadline', 'sta.escalation_alert_at', 'sta.started_at',
                'sta.notes', 'sta.rejection_reason', 'sta.updated_at'
            )
            ->where('sta.assigned_to_user_id', $user->id)
            ->orderByDesc('sta.updated_at');

        if ($status) {
            $q->where('sta.status', $status);
        } else {
            // Bawaan: yang masih perlu dikerjakan. Tugas yang sudah
            // TERVERIFIKASI/APPROVED tidak lagi membebani petugas.
            $q->whereIn('sta.status', [
                SlaTrackerService::STATUS_PENDING,
                SlaTrackerService::STATUS_IN_PROGRESS,
                SlaTrackerService::STATUS_REVISI,
                SlaTrackerService::STATUS_ESCALATED,
                SlaTrackerService::STATUS_MENUNGGU_VERIFIKASI,
            ]);
        }

        $rows = $q->limit(200)->get();

        return response()->json([
            'petugas' => ['id' => $user->id, 'nama' => $user->name],
            'jumlah'  => $rows->count(),
            'data'    => $rows,
        ]);
    }

    /**
     * POST /api/bidang-pajak/lapangan/{id}/ajukan
     * Petugas mengajukan hasil pekerjaannya untuk diverifikasi Kasubid.
     */
    public function ajukan(Request $request, int $id)
    {
        $validated = $request->validate([
            'catatan' => 'nullable|string|max:2000',
        ]);

        try {
            $hasil = $this->lapangan->ajukanHasil(
                $id,
                (int) $request->user()->id,
                $validated['catatan'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Hasil pekerjaan diajukan. Menunggu verifikasi Kasubid — '
                . 'status belum selesai sampai Kasubid memverifikasi.',
            'id'      => $id,
            'status'  => $hasil['status'],
            'tahapan_verifikasi' => $hasil['stage'],
        ]);
    }

    // ==================================================================
    // SISI KASUBID
    // ==================================================================

    /**
     * GET /api/bidang-pajak/lapangan/daftar
     * Antrian tugas yang menunggu verifikasi Kasubid, tersaring wilayah.
     */
    public function daftar(Request $request)
    {
        $hasil = $this->lapangan->antrian($request->user(), [
            'layanan' => $request->query('layanan'),
            'stage'   => $request->query('stage'),
        ]);

        return response()->json([
            'domain'  => $hasil['domain'],
            'layanan' => ServiceAccessMatrix::servicesForRole($request->user()->role ?? null),
            'jumlah'  => $hasil['jumlah'],
            'data'    => $hasil['data'],
        ]);
    }

    /**
     * GET /api/bidang-pajak/lapangan/{id}
     */
    public function detail(Request $request, int $id)
    {
        $hasil = $this->lapangan->detail($request->user(), $id);

        if (! $hasil) {
            return response()->json([
                'message' => 'Tugas tidak ditemukan atau berada di luar wilayah Anda.',
            ], 404);
        }

        return response()->json($hasil);
    }

    /**
     * POST /api/bidang-pajak/lapangan/{id}/verifikasi
     * Kasubid MENYATAKAN SELESAI. Hanya dari sini kata "selesai" berasal.
     */
    public function verifikasi(Request $request, int $id)
    {
        $validated = $request->validate([
            'catatan' => 'nullable|string|max:2000',
        ]);

        if (! $this->dalamWilayah($request, $id)) {
            return response()->json([
                'message' => 'Tugas ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        try {
            $hasil = $this->lapangan->verifikasi(
                $id,
                (int) $request->user()->id,
                $validated['catatan'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Hasil pekerjaan TERVERIFIKASI. Diteruskan ke Kepala Bidang.',
            'id'      => $id,
            'status'  => $hasil['status'],
        ]);
    }

    /**
     * POST /api/bidang-pajak/lapangan/{id}/tolak
     * Kembalikan ke petugas untuk diperbaiki. Catatan WAJIB.
     */
    public function tolak(Request $request, int $id)
    {
        $validated = $request->validate([
            'catatan' => 'required|string|min:5|max:2000',
        ]);

        if (! $this->dalamWilayah($request, $id)) {
            return response()->json([
                'message' => 'Tugas ini berada di luar wilayah penugasan Anda.',
            ], 403);
        }

        try {
            $hasil = $this->lapangan->tolak(
                $id,
                (int) $request->user()->id,
                $validated['catatan']
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pekerjaan dikembalikan ke petugas untuk diperbaiki.',
            'id'      => $id,
            'status'  => $hasil['status'],
            'catatan' => $hasil['catatan'],
        ]);
    }

    // ==================================================================
    // INTERNAL
    // ==================================================================

    /**
     * Apakah tugas ini di dalam wilayah Kasubid yang mengakses?
     *
     * Fail-closed: tanpa domain -> tidak boleh. Ini mencegah Kasubid
     * Wilayah I memverifikasi tugas Wilayah II.
     */
    private function dalamWilayah(Request $request, int $id): bool
    {
        $user = $request->user();
        $domain = TaxDomainHelper::getDomain($user);

        if ($domain === TaxDomainHelper::DOMAIN_ALL) {
            return true;
        }

        if (empty($domain)) {
            return false;
        }

        $row = DB::table('service_task_assignments')->where('id', $id)->first();
        if (! $row) {
            return false;
        }

        if ($row->tax_domain !== null && $row->tax_domain !== '') {
            return $row->tax_domain === $domain;
        }

        // Data lama tanpa tax_domain -> turunkan dari objek pajaknya.
        if ($row->reference_type === 'tax_object' && $row->reference_id) {
            $cls = DB::table('tax_objects')
                ->where('id', $row->reference_id)
                ->value('retribution_classification_id');

            $typeIds = TaxDomainHelper::getRetributionTypeIds($domain);

            return $cls !== null && in_array((int) $cls, array_map('intval', $typeIds), true);
        }

        return false;
    }
}

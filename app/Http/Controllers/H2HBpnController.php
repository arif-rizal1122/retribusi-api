<?php

namespace App\Http\Controllers;

use App\Models\SpptRecord;
use App\Models\TransactionPbb;
use App\Models\BphtbSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * H2H BPN — endpoint yang dipanggil oleh sistem BPN (eksternal).
 * Sesuai dokumen (113): tahap 1 alur BPHTB = Cek Tunggakan PBB.
 * BPN memanggil M-PAD untuk memvalidasi status tunggakan PBB sebelum proses BPHTB dilanjutkan.
 */
class H2HBpnController extends Controller
{
    /**
     * POST /api/h2h/bpn/check-pbb
     * Body: { nop: string, tahun_pajak?: int }
     *
     * Autentikasi: header X-H2H-Key vs env BPN_H2H_KEY.
     * Jika BPN_H2H_KEY kosong (belum PKS) → dev mode, endpoint tetap bisa diuji.
     */
    public function checkPbb(Request $request)
    {
        $expectedKey = config('services.bpn.h2h_key', '');
        $providedKey = (string) $request->header('X-H2H-Key', '');

        if ($expectedKey !== '' && ! hash_equals($expectedKey, $providedKey)) {
            Log::warning('H2H BPN check-pbb unauthorized', [
                'ip' => $request->ip(),
                'nop' => $request->input('nop'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: X-H2H-Key tidak valid.',
            ], 401);
        }

        $request->validate([
            'nop' => 'required|string|max:50',
            'tahun_pajak' => 'nullable|integer|min:2000|max:2100',
        ]);

        $nop = $request->nop;
        $tahun = $request->tahun_pajak ? (int) $request->tahun_pajak : null;

        // 1. Ambil SPPT records utk NOP (semua tahun / tahun tertentu)
        $spptQuery = SpptRecord::where('nop', $nop);
        if ($tahun) {
            $spptQuery->where('tax_year', $tahun);
        }
        $sppts = $spptQuery->orderBy('tax_year')->get();

        // 2. Ambil pembayaran lunas (TransactionPbb status paid + NTPD)
        $paidQuery = TransactionPbb::where('nop', $nop)
            ->where('payment_status', 'paid')
            ->whereNotNull('ntpd');
        if ($tahun) {
            $paidQuery->where('tahun', $tahun);
        }
        $paid = $paidQuery->get();

        $paidYears = $paid->pluck('tahun')
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->values()
            ->all();

        $rincian = $sppts->map(function ($s) use ($paidYears) {
            $isPaid = in_array((int) $s->tax_year, $paidYears, true)
                || strtolower((string) $s->payment_status) === 'paid';

            return [
                'tahun' => (int) $s->tax_year,
                'jumlah_pajak' => (float) $s->tax_amount,
                'jatuh_tempo' => $s->due_date,
                'status' => $isPaid ? 'lunas' : 'tunggakan',
                'payment_status' => $s->payment_status,
            ];
        });

        $tunggakan = collect($rincian)->filter(fn ($r) => $r['status'] === 'tunggakan');
        $totalTunggakan = (float) $tunggakan->sum('jumlah_pajak');

        Log::info('H2H BPN check-pbb', [
            'ip' => $request->ip(),
            'nop' => $nop,
            'tahun' => $tahun,
            'ada_tunggakan' => $tunggakan->count() > 0,
            'total' => $totalTunggakan,
        ]);

        return response()->json([
            'status' => 'ok',
            'nop' => $nop,
            'tahun_pajak' => $tahun,
            'ada_tunggakan' => $tunggakan->count() > 0,
            'total_tunggakan' => $totalTunggakan,
            'jumlah_tunggakan' => $tunggakan->count(),
            'rincian' => $rincian->values(),
            'waktu_cek' => now()->toIso8601String(),
        ]);
    }

    /**
     * F3.2 — Bridging lunas ke BPN: verifikasi status pembayaran BPHTB.
     * BPN pull via billing_code atau NTPD.
     * POST /api/h2h/bpn/verify-payment
     * Body: { billing_code?: string, ntpd?: string }
     */
    public function verifyPayment(Request $request)
    {
        $expectedKey = config('services.bpn.h2h_key', '');
        $providedKey = (string) $request->header('X-H2H-Key', '');

        if ($expectedKey !== '' && ! hash_equals($expectedKey, $providedKey)) {
            Log::warning('H2H BPN verify-payment unauthorized', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: X-H2H-Key tidak valid.',
            ], 401);
        }

        $request->validate([
            'billing_code' => 'required_without:ntpd|string|max:64',
            'ntpd' => 'required_without:billing_code|string|max:64',
        ]);

        $submission = null;

        if ($request->filled('billing_code')) {
            $submission = BphtbSubmission::where('billing_code', $request->billing_code)
                ->with('bill')
                ->first();
        } elseif ($request->filled('ntpd')) {
            $submission = BphtbSubmission::where('ntpd', $request->ntpd)
                ->with('bill')
                ->first();
        }

        if (! $submission) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Tagihan BPHTB tidak ditemukan.',
            ], 404);
        }

        Log::info('H2H BPN verify-payment', [
            'ip' => $request->ip(),
            'submission_id' => $submission->id,
            'lunas' => $submission->isPaid(),
        ]);

        return response()->json([
            'status' => 'ok',
            'lunas' => $submission->isPaid(),
            'data' => [
                'billing_code' => $submission->billing_code,
                'sspd_number' => $submission->sspd_number,
                'ntpd' => $submission->ntpd,
                'nop' => $submission->nop,
                'nib' => $submission->nib,
                'nama_wp' => $submission->nama_wp,
                'amount' => $submission->bill?->amount,
                'paid_at' => $submission->paid_at?->toIso8601String(),
                'status' => $submission->status,
            ],
            'waktu_cek' => now()->toIso8601String(),
        ]);
    }

    /**
     * F3.2 — Endpoint SPLP update (KKP BPN -> M-PAD).
     *
     * BPN mengirim update status SPLP (Surat Perintah Lanjut Proses) untuk
     * sebuah submission BPHTB yang sudah lunas. Data disimpan di kolom
     * splp_* pada bphtb_submissions dan dicatat di h2h_connector_logs.
     *
     * POST /api/h2h/bpn/splp/update
     * Body: { billing_code?: string, ntpd?: string, splp_number: string,
     *         splp_status?: string, splp_issued_at?: datetime, notes?: string }
     */
    public function updateSplp(Request $request)
    {
        $expectedKey = config('services.bpn.h2h_key', '');
        $providedKey = (string) $request->header('X-H2H-Key', '');

        if ($expectedKey !== '' && ! hash_equals($expectedKey, $providedKey)) {
            Log::warning('H2H BPN splp-update unauthorized', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: X-H2H-Key tidak valid.',
            ], 401);
        }

        $request->validate([
            'billing_code' => 'required_without:ntpd|string|max:64',
            'ntpd' => 'required_without:billing_code|string|max:64',
            'splp_number' => 'required|string|max:64',
            'splp_status' => 'nullable|string|max:50',
            'splp_issued_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $query = $request->filled('billing_code')
            ? BphtbSubmission::where('billing_code', $request->billing_code)
            : BphtbSubmission::where('ntpd', $request->ntpd);

        $submission = $query->first();

        if (! $submission) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Tagihan BPHTB tidak ditemukan.',
            ], 404);
        }

        $submission->update([
            'splp_number' => $request->splp_number,
            'splp_status' => $request->splp_status ?? 'issued',
            'splp_issued_at' => $request->splp_issued_at
                ? \Carbon\Carbon::parse($request->splp_issued_at)
                : now(),
            'splp_notes' => $request->notes,
        ]);

        \App\Models\H2hConnectorLog::record(
            'bhumi',
            'splp_update',
            '/api/splp/update',
            'Update SPLP diterima dari BPN',
            [
                'submission_id' => $submission->id,
                'billing_code' => $submission->billing_code,
                'splp_number' => $request->splp_number,
                'splp_status' => $request->splp_status ?? 'issued',
            ]
        );

        Log::info('H2H BPN splp-update', [
            'ip' => $request->ip(),
            'submission_id' => $submission->id,
            'splp_number' => $request->splp_number,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'SPLP berhasil diupdate.',
            'data' => [
                'billing_code' => $submission->billing_code,
                'ntpd' => $submission->ntpd,
                'nop' => $submission->nop,
                'splp_number' => $submission->splp_number,
                'splp_status' => $submission->splp_status,
                'splp_issued_at' => $submission->splp_issued_at?->toIso8601String(),
                'lunas' => $submission->isPaid(),
            ],
        ]);
    }
}

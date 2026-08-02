<?php

namespace App\Http\Controllers;

use App\Models\SpptRecord;
use App\Models\TransactionPbb;
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
}

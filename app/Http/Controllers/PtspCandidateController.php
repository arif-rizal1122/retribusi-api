<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PtspCandidateController extends Controller
{
    /**
     * Daftar calon wajib pajak (penyempurnaan data diri) — dashboard admin PTSP.
     * GET /api/ptsp/candidates
     */
    public function index(Request $request)
    {
        if (!Schema::hasTable('ptsp_data_candidates')) {
            return response()->json(['success' => true, 'data' => ['data' => []]]);
        }

        $query = DB::table('ptsp_data_candidates');

        if ($request->has('status') && in_array($request->status, ['menunggu', 'proses_ptsp', 'nib_terbit'])) {
            $query->where('status', $request->status);
        }
        if ($request->has('q') && $request->q) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('nama', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('jenis_usaha', 'like', "%{$q}%");
            });
        }

        $result = $query->orderByDesc('id')->paginate(20);
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Tandai NIB terbit untuk candidate — update status + catat notifikasi.
     * POST /api/ptsp/candidates/{id}/nib  body: {nib}
     */
    public function markNib(Request $request, $id)
    {
        $request->validate(['nib' => 'required|string|size:16']);

        if (!Schema::hasTable('ptsp_data_candidates')) {
            return response()->json(['success' => false, 'message' => 'Fitur belum tersedia'], 503);
        }

        $candidate = DB::table('ptsp_data_candidates')->where('id', $id)->first();
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Candidate tidak ditemukan'], 404);
        }

        DB::table('ptsp_data_candidates')
            ->where('id', $id)
            ->update([
                'status' => 'nib_terbit',
                'nib'    => $request->nib,
                'updated_at' => now(),
            ]);

        // Simpan mapping NIB (kalau belum ada)
        if (Schema::hasTable('nib_nop_mappings')) {
            $exists = DB::table('nib_nop_mappings')->where('nib', $request->nib)->exists();
            if (!$exists) {
                DB::table('nib_nop_mappings')->insert([
                    'nib'              => $request->nib,
                    'nop'              => null,
                    'taxpayer_name'    => $candidate->nama,
                    'land_area_m2'     => null,
                    'building_area_m2' => null,
                    'znt'              => null,
                    'match_status'     => 'pending',
                    'source'           => 'manual',
                    'last_synced_at'   => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        // Catat notifikasi (channel wa — via notification_logs)
        if (Schema::hasTable('notification_logs')) {
            DB::table('notification_logs')->insert([
                'taxpayer_id'     => null,
                'phone'           => $candidate->no_hp,
                'channel'         => 'wa',
                'type'            => 'nib_terbit',
                'reference_type'  => 'ptsp_data_candidates',
                'reference_id'    => (int) $id,
                'message_preview' => "Selamat {$candidate->nama}, NIB Anda {$request->nib} sudah tercatat di M-PAD. Silakan lanjutkan pendaftaran layanan. — Bapenda Baubau",
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "NIB {$request->nib} tercatat untuk {$candidate->nama}. Notifikasi dikirim.",
        ]);
    }
}

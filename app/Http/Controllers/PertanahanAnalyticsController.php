<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PbbNopApplication;
use App\Models\PbbMutation;

/**
 * Analitik SLA & Penghambat Verifikasi — Dinas Pertanahan.
 *
 * Dasar aturan: SLA verifikasi maks 3 hari kerja.
 * Semua angka dihitung dari data nyata. TIDAK ADA fallback palsu —
 * bila data kosong, kembalikan 0 / null apa adanya, jangan menebak.
 */
class PertanahanAnalyticsController extends Controller
{
    /** Batas SLA verifikasi (hari). */
    public const SLA_HARI = 3;

    /**
     * GET /api/pertanahan/sla
     * Ringkasan SLA verifikasi: tepat waktu, mendekati batas, lewat batas.
     */
    public function sla(Request $request)
    {
        $now = now();
        $sla = self::SLA_HARI;

        // Permohonan yang MASIH berjalan (belum putus) — ini yang dihitung terhadap SLA
        $berjalan = PbbNopApplication::whereIn('status', ['PENDING', 'SURVEY'])->get();

        $tepatWaktu = [];
        $mendekati = [];
        $lewat = [];

        foreach ($berjalan as $a) {
            $umurJam = $a->created_at ? $a->created_at->diffInHours($now) : 0;
            $umurHari = round($umurJam / 24, 1);
            $sisaHari = round($sla - $umurHari, 1);

            $item = [
                'id' => $a->id,
                'nama' => $a->name,
                'nik' => $a->nik,
                'status' => $a->status,
                'nop' => $a->nop,
                'nib' => $a->nib,
                'diajukan' => optional($a->created_at)->toIso8601String(),
                'umur_hari' => $umurHari,
                'sisa_hari' => $sisaHari,
            ];

            if ($umurHari > $sla) {
                $lewat[] = $item;
            } elseif ($sisaHari <= 1) {
                $mendekati[] = $item;
            } else {
                $tepatWaktu[] = $item;
            }
        }

        // Yang SUDAH selesai — ukur lama penyelesaian aktual
        $selesai = PbbNopApplication::whereIn('status', ['APPROVED', 'REJECTED'])
            ->whereNotNull('updated_at')
            ->get();

        $durasiSelesai = [];
        foreach ($selesai as $s) {
            if ($s->created_at && $s->updated_at) {
                $durasiSelesai[] = round($s->created_at->diffInHours($s->updated_at) / 24, 1);
            }
        }

        return response()->json([
            'success' => true,
            'sla_hari' => $sla,
            'ringkasan' => [
                'berjalan' => count($berjalan),
                'tepat_waktu' => count($tepatWaktu),
                'mendekati_batas' => count($mendekati),
                'lewat_batas' => count($lewat),
                'selesai' => count($selesai),
            ],
            'rata_durasi_selesai_hari' => count($durasiSelesai)
                ? round(array_sum($durasiSelesai) / count($durasiSelesai), 2)
                : null,
            'daftar' => [
                'lewat_batas' => $lewat,
                'mendekati_batas' => $mendekati,
                'tepat_waktu' => $tepatWaktu,
            ],
        ]);
    }

    /**
     * GET /api/pertanahan/hambatan
     * Petakan hal yang menghambat durasi verifikasi — dihitung dari kelengkapan data nyata.
     */
    public function hambatan(Request $request)
    {
        $semua = PbbNopApplication::all();
        $total = $semua->count();

        if ($total === 0) {
            return response()->json([
                'success' => true,
                'total_permohonan' => 0,
                'hambatan' => [],
                'catatan' => 'Belum ada permohonan untuk dianalisis.',
            ]);
        }

        // Definisi penghambat — tiap satu diperiksa dari kolom nyata
        $definisi = [
            [
                'kode' => 'NIB_BELUM_FIX',
                'label' => 'NIB belum fix di BPN',
                'dampak' => 'Petugas tidak dapat ke lapangan sebelum NIB fix',
                'uji' => fn($a) => empty($a->nib),
            ],
            [
                'kode' => 'BPN_BELUM_VERIFIKASI',
                'label' => 'Belum terverifikasi BPN',
                'dampak' => 'Data bidang belum tervalidasi pusat',
                'uji' => fn($a) => !$a->is_bpn_verified,
            ],
            [
                'kode' => 'TANPA_FOTO_SURVEY',
                'label' => 'Tidak ada foto survei lapangan',
                'dampak' => 'Tidak ada bukti petugas turun ke lapangan',
                'uji' => fn($a) => empty($a->survey_photo_path),
            ],
            [
                'kode' => 'TANPA_CATATAN_SURVEY',
                'label' => 'Tidak ada catatan survei',
                'dampak' => 'Temuan lapangan tidak terdokumentasi',
                'uji' => fn($a) => empty($a->survey_notes),
            ],
            [
                'kode' => 'TANPA_IMB',
                'label' => 'Berkas IMB/PBG tidak ada',
                'dampak' => 'Menghambat verifikasi alas hak bangunan',
                'uji' => fn($a) => empty($a->imb_file_path),
            ],
            [
                'kode' => 'TANPA_NOP',
                'label' => 'NOP belum diterbitkan',
                'dampak' => 'Bidang disetujui tapi belum punya NOP',
                'uji' => fn($a) => empty($a->nop),
            ],
            [
                'kode' => 'TANPA_KTP',
                'label' => 'Berkas KTP tidak ada',
                'dampak' => 'Identitas pemohon tidak terverifikasi',
                'uji' => fn($a) => empty($a->ktp_file_path),
            ],
            [
                'kode' => 'TANPA_AKTA',
                'label' => 'Berkas akta/alas hak tidak ada',
                'dampak' => 'Kepemilikan tidak terbukti',
                'uji' => fn($a) => empty($a->akte_file_path),
            ],
        ];

        $hasil = [];
        foreach ($definisi as $d) {
            $terkena = $semua->filter($d['uji'])->count();
            if ($terkena === 0) {
                continue; // tidak menghambat kalau tidak ada yang terkena
            }
            $hasil[] = [
                'kode' => $d['kode'],
                'label' => $d['label'],
                'dampak' => $d['dampak'],
                'jumlah' => $terkena,
                'persen' => round($terkena * 100 / $total, 1),
            ];
        }

        // Urut dari penghambat terbesar
        usort($hasil, fn($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return response()->json([
            'success' => true,
            'total_permohonan' => $total,
            'jumlah_penghambat' => count($hasil),
            'hambatan' => $hasil,
        ]);
    }

    /**
     * GET /api/pertanahan/ringkasan
     * Ringkasan kanal pertanahan: permohonan NOP, mutasi, NIB terbit.
     * Semua dari data nyata.
     */
    public function ringkasan(Request $request)
    {
        $nopPerStatus = PbbNopApplication::select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $mutasiPerStatus = PbbMutation::select('status', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        // NIB kavling yang sudah terbit (data nyata dari pbb_split_items)
        $nibTerbit = DB::table('pbb_split_items')->whereNotNull('nib')->count();
        $kavlingTotal = DB::table('pbb_split_items')->count();

        return response()->json([
            'success' => true,
            'permohonan_nop' => [
                'total' => PbbNopApplication::count(),
                'per_status' => $nopPerStatus,
                'terverifikasi_bpn' => PbbNopApplication::where('is_bpn_verified', true)->count(),
                'punya_nib' => PbbNopApplication::whereNotNull('nib')->count(),
                'punya_nop' => PbbNopApplication::whereNotNull('nop')->count(),
            ],
            'mutasi_pbb' => [
                'total' => PbbMutation::count(),
                'per_status' => $mutasiPerStatus,
            ],
            'nib_kavling' => [
                'total_kavling' => $kavlingTotal,
                'sudah_terbit' => $nibTerbit,
                'belum_terbit' => $kavlingTotal - $nibTerbit,
            ],
        ]);
    }
}

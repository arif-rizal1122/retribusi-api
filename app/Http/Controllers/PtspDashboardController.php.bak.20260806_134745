<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard Khusus Admin PTSP (DPMPTSP) — role: admin_ptsp
 *
 * Menyediakan statistik NIB (dari tabel nib_nop_mappings hasil sinkronisasi
 * BHUMI/OSS), tren pertumbuhan NIB, daftar layanan M-PAD yang terkait PTSP,
 * dan daftar warga/pelaku usaha beserta status kepemilikan NIB.
 *
 * CATATAN KOMPATIBILITAS:
 * Tabel nib_nop_mappings & taxpayer_nops hanya ada di database PRODUKSI
 * (retribusi), belum ada di database dev (retribusi_dev). Seluruh query ke
 * tabel tersebut dijaga dengan Schema::hasTable() agar endpoint tetap
 * berfungsi (mengembalikan data kosong/0) di lingkungan yang tabelnya belum
 * dimigrasi — tanpa perlu menjalankan migration dari sisi dev.
 */
class PtspDashboardController extends Controller
{
    /**
     * ID klasifikasi layanan M-PAD yang terkait dengan PTSP (DPMPTSP).
     */
    private const PTSP_RELATED_CLASSIFICATION_IDS = [
        212, 213, // PZ-PBG, PZ-TKA  (Izin terbit PTSP)
        188, 189, 190, // REKLAME, MBLB, WALET
        192, 193, 194, 195, // PBJT-FOOD, PBJT-HTL, PBJT-HBR, PBJT-HBR-SP
        197, // AIR-TANAH
        203, 204, 206, 207, 208, 209, 210, 211, // JU-TKU, JU-PEL, JU-VILA, JU-RPH, JU-PELAB, JU-REK, JU-PROD, JU-ASET
        201, // RET-KES
        186, 187, // PBB-P2, BPHTB (data pendukung)
    ];

    /**
     * Tentukan jenis keterkaitan layanan M-PAD dengan PTSP berdasarkan kode klasifikasi.
     */
    private function keterkaitan(string $code, int $id): string
    {
        $code = strtoupper(trim($code));

        // Izin yang diterbitkan langsung oleh PTSP/DPMPTSP
        if (str_starts_with($code, 'PZ-')) {
            return 'Izin terbit PTSP';
        }

        // Data pendukung validasi (bukan izin, bukan syarat NIB)
        if ($code === 'PBB-P2' || $code === 'BPHTB' || in_array($id, [186, 187], true)) {
            return 'Data pendukung (validasi)';
        }

        // Layanan usaha yang mensyaratkan NIB sebagai identitas usaha
        return 'Wajib NIB untuk usaha';
    }

    /**
     * Statistik utama dashboard PTSP.
     *
     * GET /api/admin/ptsp/dashboard/stats
     */
    public function stats(Request $request)
    {
        $nibTableExists = Schema::hasTable('nib_nop_mappings');

        $nib = [
            'total'      => 0,
            'pending'    => 0,
            'matched'    => 0,
            'conflict'   => 0,
            'not_found'  => 0,
        ];

        if ($nibTableExists) {
            $nib['total'] = DB::table('nib_nop_mappings')->count();

            $byStatus = DB::table('nib_nop_mappings')
                ->select('match_status', DB::raw('COUNT(*) as total'))
                ->groupBy('match_status')
                ->pluck('total', 'match_status');

            foreach (['pending', 'matched', 'conflict', 'not_found'] as $status) {
                $nib[$status] = (int) ($byStatus[$status] ?? 0);
            }
        }

        $totalTaxpayers = DB::table('taxpayers')->count();

        // Pelaku usaha ber-NIB = jumlah nama taxpayer unik yang sudah punya mapping NIB
        $pelakuUsahaBerNib = 0;
        if ($nibTableExists) {
            $pelakuUsahaBerNib = DB::table('nib_nop_mappings')
                ->whereNotNull('taxpayer_name')
                ->where('taxpayer_name', '!=', '')
                ->distinct()
                ->count('taxpayer_name');
        }

        $totalLayananTerkait = DB::table('retribution_classifications')
            ->whereIn('id', self::PTSP_RELATED_CLASSIFICATION_IDS)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'nib' => $nib,
                'total_taxpayers' => $totalTaxpayers,
                'pelaku_usaha_ber_nib' => $pelakuUsahaBerNib,
                'total_layanan_terkait' => $totalLayananTerkait,
                'meta' => [
                    'nib_table_exists' => $nibTableExists,
                ],
            ],
        ]);
    }

    /**
     * Tren pertumbuhan NIB per bulan (+ sektor bila tersedia).
     *
     * GET /api/admin/ptsp/dashboard/growth
     */
    public function growth(Request $request)
    {
        $monthly = [];
        $sektor = [];
        $nibTableExists = Schema::hasTable('nib_nop_mappings');

        if ($nibTableExists) {
            $monthly = DB::table('nib_nop_mappings')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
                ->whereNotNull('created_at')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all();

            // Kolom sektor tidak ada di skema awal nib_nop_mappings; jika suatu saat
            // ditambahkan (mis. lewat sinkronisasi BHUMI), grouping otomatis aktif.
            $sektorColumn = Schema::hasColumn('nib_nop_mappings', 'sektor') ? 'sektor'
                : (Schema::hasColumn('nib_nop_mappings', 'sector') ? 'sector' : null);

            if ($sektorColumn) {
                $sektor = DB::table('nib_nop_mappings')
                    ->select($sektorColumn, DB::raw('COUNT(*) as total'))
                    ->whereNotNull($sektorColumn)
                    ->groupBy($sektorColumn)
                    ->orderByDesc('total')
                    ->get()
                    ->map(fn ($row) => [
                        'sektor' => $row->{$sektorColumn},
                        'total'  => (int) $row->total,
                    ])
                    ->values()
                    ->all();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'monthly' => $monthly,
                'sektor' => $sektor,
            ],
        ]);
    }

    /**
     * Daftar layanan M-PAD yang terkait PTSP beserta jenis keterkaitannya.
     *
     * GET /api/admin/ptsp/dashboard/related-services
     */
    public function relatedServices(Request $request)
    {
        $services = DB::table('retribution_classifications')
            ->whereIn('id', self::PTSP_RELATED_CLASSIFICATION_IDS)
            ->select('id', 'name', 'code', 'retribution_type_id')
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'                 => (int) $s->id,
                'name'               => $s->name,
                'code'               => $s->code,
                'retribution_type_id'=> (int) $s->retribution_type_id,
                'keterkaitan'        => $this->keterkaitan((string) $s->code, (int) $s->id),
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => count($services),
                'services' => $services,
            ],
        ]);
    }

    /**
     * Daftar warga/pelaku usaha beserta status kepemilikan NIB.
     *
     * GET /api/admin/ptsp/dashboard/taxpayers?limit=100&search=
     *
     * Strategi join (hasil eksplorasi skema):
     * - taxpayers.id            → taxpayer_pbb_objects.taxpayer_id   (relasi 1:N, objek PBB)
     * - taxpayers.id            → taxpayer_nops.taxpayer_id         (relasi 1:N, NOP legacy, bila ada)
     * - nib_nop_mappings        tidak punya FK ke taxpayers; kecocokan dilakukan via:
     *     (a) taxpayers.name  = nib_nop_mappings.taxpayer_name  (case-insensitive)
     *     (b) NOP milik taxpayer (dari taxpayer_pbb_objects/taxpayer_nops) = nib_nop_mappings.nop
     */
    public function taxpayers(Request $request)
    {
        $limit = min((int) $request->query('limit', 100), 500);
        $search = trim((string) $request->query('search', ''));

        $nibTableExists = Schema::hasTable('nib_nop_mappings');
        $taxpayerNopsExists = Schema::hasTable('taxpayer_nops');

        // 1) Kumpulkan NOP per taxpayer dari kedua sumber relasi
        $nopsByTaxpayer = [];

        $pbbNops = DB::table('taxpayer_pbb_objects')
            ->select('taxpayer_id', 'nop')
            ->whereNotNull('nop')
            ->get();
        foreach ($pbbNops as $row) {
            $nopsByTaxpayer[(int) $row->taxpayer_id][strtoupper(trim($row->nop))] = true;
        }

        if ($taxpayerNopsExists) {
            $legacyNops = DB::table('taxpayer_nops')
                ->select('taxpayer_id', 'nop')
                ->whereNotNull('nop')
                ->get();
            foreach ($legacyNops as $row) {
                $nopsByTaxpayer[(int) $row->taxpayer_id][strtoupper(trim($row->nop))] = true;
            }
        }

        // 2) Kumpulkan seluruh mapping NIB sekali (nama → nib, nop → nib)
        $nibByName = []; // strtolower(taxpayer_name) → nib record
        $nibByNop = [];  // strtoupper(nop) → nib record

        if ($nibTableExists) {
            $mappings = DB::table('nib_nop_mappings')->get();
            foreach ($mappings as $m) {
                $record = [
                    'nib'          => $m->nib,
                    'match_status' => $m->match_status,
                    'source'       => $m->source,
                    'last_synced_at' => $m->last_synced_at,
                ];
                if ($m->taxpayer_name && trim($m->taxpayer_name) !== '') {
                    $nibByName[strtolower(trim($m->taxpayer_name))] = $record;
                }
                if ($m->nop && trim($m->nop) !== '') {
                    $nibByNop[strtoupper(trim($m->nop))] = $record;
                }
            }
        }

        // 3) Query taxpayers (tanpa global scope model agar konsisten lintas role)
        $query = DB::table('taxpayers')
            ->select('id', 'nik', 'name', 'address', 'district', 'sub_district', 'phone', 'npwpd', 'is_active', 'created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('npwpd', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('created_at')->limit($limit)->get();

        // 4) Tentukan status NIB tiap taxpayer
        $result = $rows->map(function ($t) use ($nopsByTaxpayer, $nibByName, $nibByNop) {
            $nops = array_keys($nopsByTaxpayer[(int) $t->id] ?? []);

            $matchedNib = null;
            $matchedBy = null;

            // Prioritas 1: kecocokan via NOP (lebih presisi)
            foreach ($nops as $nop) {
                if (isset($nibByNop[$nop])) {
                    $matchedNib = $nibByNop[$nop];
                    $matchedBy = 'nop';
                    break;
                }
            }

            // Prioritas 2: kecocokan via nama taxpayer
            if (!$matchedNib && $t->name && isset($nibByName[strtolower(trim($t->name))])) {
                $matchedNib = $nibByName[strtolower(trim($t->name))];
                $matchedBy = 'name';
            }

            return [
                'id'          => (int) $t->id,
                'name'        => $t->name,
                'nik'         => $t->nik,
                'npwpd'       => $t->npwpd,
                'address'     => $t->address,
                'district'    => $t->district,
                'sub_district'=> $t->sub_district,
                'phone'       => $t->phone,
                'is_active'   => (bool) $t->is_active,
                'created_at'  => $t->created_at,
                'nops'        => $nops,
                'nib'         => $matchedNib ? $matchedNib['nib'] : null,
                'match_status'=> $matchedNib ? $matchedNib['match_status'] : null,
                'nib_source'  => $matchedNib ? $matchedNib['source'] : null,
                'matched_by'  => $matchedBy,
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'limit' => $limit,
                'taxpayers' => $result,
            ],
        ]);
    }
}

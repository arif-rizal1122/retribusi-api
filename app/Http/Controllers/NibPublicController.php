<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NibPublicController extends Controller
{
    // 18 layanan usaha wajib NIB + 2 izin PTSP (dari relatedServices mapping)
    const WAJIB_NIB_IDS = [188, 189, 190, 192, 193, 194, 195, 197, 201, 203, 204, 206, 207, 208, 209, 210, 211, 212, 213];

    // Kontak resmi DPMPTSP Baubau (hasil scraping dpmptsp.baubaukota.go.id, 6 Agu 2026)
    const PTSP_ALAMAT = 'Jln. Raya Palagimata No., Kel. Lipu, Kec. Betoambari, Kota Baubau';
    const PTSP_EMAIL  = 'baubaudpmptsp@gmail.com';
    const PTSP_TELPON = '0813 4152 7767';
    const PTSP_WEB    = 'https://dpmptsp.baubaukota.go.id';

    /**
     * Cek status NIB secara publik (mobile & web).
     * GET /api/public/nib/check?nib=XXXXXXXX
     */
    public function check(Request $request)
    {
        $request->validate(['nib' => 'required|string|size:16']);

        $nib = $request->nib;
        $data = null;

        if (Schema::hasTable('nib_nop_mappings')) {
            $data = DB::table('nib_nop_mappings')->where('nib', $nib)->first();
        }

        if (!$data) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nib'   => $nib,
                    'status' => 'not_found',
                    'taxpayer_name' => null,
                    'nop' => null,
                    'layanan_terkait' => $this->layananWajibNib(),
                ],
                'info_ptsp' => $this->infoPtsp(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nib'   => $data->nib,
                'status' => $data->match_status,
                'taxpayer_name' => $data->taxpayer_name,
                'nop' => $data->nop,
                'layanan_terkait' => $this->layananWajibNib(),
            ],
            'info_ptsp' => $this->infoPtsp(),
        ]);
    }

    /**
     * Simpan candidate "Penyempurnaan Data Diri" — warga yang belum punya NIB.
     * POST /api/public/nib/candidate
     */
    public function storeCandidate(Request $request)
    {
        $request->validate([
            'nama'         => 'required|string|max:255',
            'nik'          => 'required|string|size:16',
            'alamat_usaha' => 'nullable|string',
            'jenis_usaha'  => 'nullable|string|max:255',
            'no_hp'        => 'required|string|max:20',
        ]);

        if (!Schema::hasTable('ptsp_data_candidates')) {
            return response()->json(['success' => false, 'message' => 'Fitur belum tersedia'], 503);
        }

        // Cegah duplikasi: NIK + status belum selesai
        $exists = DB::table('ptsp_data_candidates')
            ->where('nik', $request->nik)
            ->whereIn('status', ['menunggu', 'proses_ptsp'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data Anda sudah terdaftar dan sedang diproses admin PTSP.',
                'code'    => 'CANDIDATE_EXISTS',
            ], 422);
        }

        $id = DB::table('ptsp_data_candidates')->insertGetId([
            'nama'         => $request->nama,
            'nik'          => $request->nik,
            'alamat_usaha' => $request->alamat_usaha,
            'jenis_usaha'  => $request->jenis_usaha,
            'no_hp'        => $request->no_hp,
            'status'       => 'menunggu',
            'nib'          => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data diterima. Admin PTSP akan menghubungi Anda setelah NIB terbit.',
            'data' => [
                'id' => $id,
                'status' => 'menunggu',
                'info_ptsp' => $this->infoPtsp(),
            ],
        ], 201);
    }

    /**
     * Enforce gate NIB saat pendaftaran layanan (dipanggil dari CitizenServiceController@register).
     * Layanan wajib NIB (18 usaha + 2 izin PTSP) tanpa NIB → 422 NIB_REQUIRED + info PTSP.
     */
    public static function enforceNibGate(Request $request, $classification, $taxpayer): void
    {
        if (!in_array((int) $classification->id, self::WAJIB_NIB_IDS, true)) {
            return; // layanan non-wajib NIB (PBB, BPHTB, dll) bebas
        }

        $nib = trim((string) $request->input('nib', ''));
        $taxpayerNib = $taxpayer->nib ?? null;

        // Cek: input nib valid & terdaftar, ATAU taxpayer sudah punya nib
        if ($nib !== '' && strlen($nib) === 16 && Schema::hasTable('nib_nop_mappings')) {
            $found = DB::table('nib_nop_mappings')->where('nib', $nib)->exists();
            if ($found) {
                // Simpan nib ke taxpayer kalau belum ada
                if (!$taxpayerNib) {
                    $taxpayer->update(['nib' => $nib]);
                }
                return;
            }
        }

        if ($taxpayerNib && strlen((string) $taxpayerNib) === 16) {
            return; // taxpayer sudah punya NIB — lanjut
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'nib' => 'Anda belum memiliki NIB. NIB wajib untuk layanan ini. Silakan urus NIB di DPMPTSP Kota Baubau.',
        ])->status(422)->errorBag('nib');
    }

    /**
     * Daftar layanan wajib NIB (untuk info di UI).
     */
    private function layananWajibNib(): array
    {
        if (!Schema::hasTable('retribution_classifications')) {
            return [];
        }
        return DB::table('retribution_classifications')
            ->whereIn('id', self::WAJIB_NIB_IDS)
            ->get(['id', 'name', 'code'])
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code])
            ->toArray();
    }

    /**
     * Info kontak PTSP untuk ditampilkan di layar rujukan.
     */
    private function infoPtsp(): array
    {
        return [
            'alamat'  => self::PTSP_ALAMAT,
            'email'   => self::PTSP_EMAIL,
            'telepon' => self::PTSP_TELPON,
            'website' => self::PTSP_WEB,
        ];
    }

    /**
     * M41-1 — Lookup data usaha dari NIB untuk auto-fill unit bisnis.
     * GET /api/public/nib/lookup?nib=XXXXXXXX
     * Sumber: nib_nop_mappings (NOP/luas) + ptsp_data_candidates (jenis/alamat usaha).
     */
    public function lookup(Request $request)
    {
        $request->validate(['nib' => 'required|string|size:16']);
        $nib = $request->nib;

        $nopData = null;
        if (Schema::hasTable('nib_nop_mappings')) {
            $nopData = DB::table('nib_nop_mappings')->where('nib', $nib)->first();
        }

        $candidate = null;
        if (Schema::hasTable('ptsp_data_candidates')) {
            $candidate = DB::table('ptsp_data_candidates')
                ->where('nib', $nib)
                ->orWhere('nik', $nib) // fallback: NIB diisi di kolom nik saat pendaftaran manual
                ->first();
        }

        if (! $nopData && ! $candidate) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nib' => $nib,
                    'found' => false,
                    'suggest' => 'NIB belum terdaftar di sistem. Lengkapi manual atau ajukan penyempurnaan data diri.',
                ],
            ]);
        }

        // Jenis usaha → mapping sektor (9 sektor kalkulator bisnis)
        $jenisUsaha = $candidate->jenis_usaha ?? null;
        $sectorId = $this->guessSector($jenisUsaha);

        return response()->json([
            'success' => true,
            'data' => [
                'nib' => $nib,
                'found' => true,
                'nama' => $candidate->nama ?? $nopData->taxpayer_name ?? null,
                'jenis_usaha' => $jenisUsaha,
                'alamat' => $candidate->alamat_usaha ?? null,
                'nop' => $nopData->nop ?? null,
                'match_status' => $nopData->match_status ?? 'pending',
                'sector_id' => $sectorId,
                'suggest' => 'Data ditemukan — periksa lalu simpan unit usaha.',
            ],
        ]);
    }

    /** Tebak sektor kalkulator dari jenis usaha (Dok 143, 9 sektor). */
    private function guessSector(?string $jenisUsaha): ?string
    {
        if (! $jenisUsaha) return null;
        $j = strtolower($jenisUsaha);
        $map = [
            'restoran' => 'restoran', 'makan' => 'warung', 'kafe' => 'warung', 'cafe' => 'warung',
            'hotel' => 'hotel', 'penginapan' => 'hotel', 'melati' => 'hotel',
            'air baku' => 'air_baku', 'air minum' => 'depot_air', 'depot' => 'depot_air',
            'laundry' => 'laundry', 'binatu' => 'laundry',
            'game' => 'game_center', 'bilyard' => 'bilyard', 'biliar' => 'bilyard',
            'hiburan' => 'hiburan_malam', 'karaoke' => 'hiburan_malam', 'diskotik' => 'hiburan_malam', 'bar' => 'hiburan_malam',
        ];
        foreach ($map as $k => $v) {
            if (str_contains($j, $k)) return $v;
        }
        return null;
    }
}

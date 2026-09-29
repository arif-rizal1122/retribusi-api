<?php

namespace App\Http\Controllers;

use App\Services\LegacyDictionaryService;
use App\Services\ObjectNumberRegistryService;
use App\Services\ObjectNumberService;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * NOPD / NORD 14 digit — pencarian, kamus legacy, dan klaim objek usaha lama.
 *
 * Lingkup: pajak daerah non-PBB/BPHTB + retribusi daerah.
 */
class ObjectNumberController extends Controller
{
    public function __construct(
        private ObjectNumberService $numbers,
        private ObjectNumberRegistryService $registry,
        private LegacyDictionaryService $legacy,
    ) {}

    /**
     * GET /api/object-numbers/sektor
     * Matriks baku kodifikasi 14 digit.
     */
    public function sektor()
    {
        $map = $this->numbers->sektorMap();
        $data = [];

        foreach ($map as $kode => $info) {
            $data[] = [
                'sektor' => $kode,
                'nama' => $info['nama'],
                'jenis' => $info['jenis'],
                'opd' => $info['opd'],
                'contoh' => $this->numbers->formatTampilan($this->numbers->generate($kode, '7101', 1)),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'kode_kota' => $this->numbers->kodeKota(),
                'format' => '7472 . SS (sektor) . KKVV (kecamatan+kelurahan) . UUUU (urut titik)',
                'panjang' => $this->numbers->panjang(),
                'sektor' => $data,
            ],
        ]);
    }

    /**
     * GET /api/object-numbers/{nomor}/kartu
     * Kartu NOPD/NORD dalam bentuk PDF (A4 portrait).
     *
     * Nomor diambil dari object_number_registry (sumber kebenaran), lalu objek pajaknya
     * ditelusuri lewat tax_object_id. NOP lama dipasang apa adanya, tetap bisa dicetak.
     */
    public function kartu(string $nomor)
    {
        $bersih = $this->numbers->normalisasiInput($nomor);
        $record = $this->registry->cariNomor($bersih);

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor objek tidak terdaftar pada registry NOPD/NORD.',
            ], 404);
        }

        $taxObject = $record->tax_object_id
            ? \App\Models\TaxObject::with(['taxpayer', 'opd'])->find($record->tax_object_id)
            : null;

        if (! $taxObject) {
            // Registry ada tapi objek pajaknya belum tertaut — tetap terbitkan kartu
            // dari data registry supaya nomor yang sudah beredar selalu bisa dicetak.
            $taxObject = new \App\Models\TaxObject([
                'id' => null,
                'name' => $record->metadata['business_name'] ?? 'Objek ' . ($record->jenis ?? 'Pajak/Retribusi'),
                'address' => $record->metadata['address_text'] ?? '-',
            ]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nopd', [
            'taxObject' => $taxObject,
            'nomor' => $record->nomor,
            'jenis' => $record->jenis,
            'sektor' => $record->sector_code,
        ])->setPaper('A4', 'portrait');

        return $pdf->download('NOPD-' . preg_replace('/[^0-9]/', '', (string) $record->nomor) . '.pdf');
    }

    /**
     * GET /api/object-numbers/{nomor}
     * Cek nomor NOPD/NORD (bisa diisi dengan/atau tanpa titik).
     */
    public function show(string $nomor)
    {
        $bersih = $this->numbers->normalisasiInput($nomor);

        if (! $this->numbers->isValid($bersih)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor objek tidak valid. Format 14 digit: 7472.SS.KKVV.UUUU',
            ], 422);
        }

        $record = $this->registry->cariNomor($bersih);

        return response()->json([
            'success' => true,
            'data' => [
                'nomor' => $bersih,
                'tampilan' => $this->numbers->formatTampilan($bersih),
                'detail' => $this->numbers->parse($bersih),
                'terdaftar' => (bool) $record,
                'claim_status' => $record->claim_status ?? null,
                'legacy_code' => $record->legacy_code ?? null,
            ],
        ]);
    }

    /**
     * GET /api/object-numbers/lookup?q=...
     * Satu kolom input untuk petugas loket: NOPD/NORD baru, NOP lama, atau NPWPD lama.
     */
    public function lookup(Request $request)
    {
        $data = $request->validate(['q' => 'required|string|min:3|max:60']);

        $hasil = $this->registry->cariApaSaja($data['q']);
        $saran = $this->legacy->saran($data['q']);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $data['q'],
                'nomor_terdaftar' => array_map(fn ($r) => [
                    'nomor' => $r->nomor,
                    'tampilan' => $this->numbers->formatTampilan($r->nomor),
                    'jenis' => $r->jenis,
                    'claim_status' => $r->claim_status,
                    'legacy_code' => $r->legacy_code,
                ], $hasil),
                'saran_kamus' => $saran->map(fn ($d) => [
                    'id' => $d->id,
                    'nama' => $d->namaTampil(),
                    'sector_code' => $d->sector_code,
                    'claim_status' => $d->claim_status,
                ])->values(),
            ],
        ]);
    }

    /**
     * POST /api/object-numbers/legacy-lookup
     * Pencarian mandiri ke kamus referensi legacy (read-only).
     */
    public function legacyLookup(Request $request)
    {
        $data = $request->validate([
            'legacy_code' => 'nullable|string|max:60',
            'npwpd' => 'nullable|string|max:30',
            'nik' => 'nullable|string|size:16',
            'nib' => 'nullable|string|max:32',
            'sector_code' => 'nullable|string|size:2',
        ]);

        if (collect($data)->filter(fn ($v) => ! empty($v))->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Isi minimal satu kunci: NOP lama, NPWPD lama, NIK, atau NIB.',
            ], 422);
        }

        $hasil = $this->legacy->cari($data);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $hasil->count(),
                'kandidat' => $hasil->map(fn ($d) => [
                    'id' => $d->id,
                    'nama_objek_usaha' => $d->namaTampil(),
                    'legal_entity_name' => $d->legal_entity_name,
                    'sector_code' => $d->sector_code,
                    'sector_nama' => $this->numbers->sektor($d->sector_code)['nama'],
                    'alamat' => $d->address_text,
                    'legacy_code' => $d->legacy_code,
                    'claim_status' => $d->claim_status,
                    'spesifikasi_teknis' => $d->technical_specs,
                ])->values(),
            ],
            'catatan' => 'Data lama hanya dipakai sebagai referensi pengayaan atribut, bukan impor transaksi.',
        ]);
    }

    /**
     * GET /api/object-numbers/kandidat-klaim?nik=...&nib=...
     * Notifikasi otomatis saat warga login (NIK terverifikasi Dukcapil/IKD).
     */
    public function kandidatKlaim(Request $request)
    {
        $data = $request->validate([
            'nik' => 'nullable|string|size:16',
            'nib' => 'nullable|string|max:32',
        ]);

        $identitas = $data['nik'] ?? $data['nib'] ?? null;
        if (empty($identitas)) {
            return response()->json(['success' => false, 'message' => 'NIK atau NIB wajib diisi.'], 422);
        }

        $hasil = $this->legacy->kandidatKlaim($identitas);

        return response()->json([
            'success' => true,
            'data' => [
                'ada_tawaran' => $hasil->isNotEmpty(),
                'pesan' => $hasil->isNotEmpty()
                    ? 'Ditemukan tempat usaha terdaftar atas nama Anda. Klik untuk klaim.'
                    : null,
                'kandidat' => $hasil->map(fn ($d) => [
                    'id' => $d->id,
                    'nama_objek_usaha' => $d->namaTampil(),
                    'sector_code' => $d->sector_code,
                    'alamat' => $d->address_text,
                ])->values(),
            ],
        ]);
    }

    /**
     * POST /api/object-numbers/claim
     * Klaim 1-klik: terbitkan NOPD/NORD baru, data profil sudah terisi otomatis.
     * Warga hanya memverifikasi titik lokasi.
     */
    public function claim(Request $request)
    {
        $data = $request->validate([
            'legacy_dictionary_id' => 'required|integer|exists:ref_legacy_dictionary,id',
            'kode_wilayah' => 'required|string|digits:4',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $hasil = $this->legacy->klaim(
                (int) $data['legacy_dictionary_id'],
                array_merge($data, [
                    'taxpayer_id' => $request->user()?->taxpayer_id,
                    'claimed_by_user_id' => $request->user()?->id,
                ]),
                $this->registry,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Objek usaha berhasil diklaim. Nomor baru sudah diterbitkan.',
            'data' => $hasil,
        ], 201);
    }
}

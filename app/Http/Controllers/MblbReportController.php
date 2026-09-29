<?php

namespace App\Http\Controllers;

use App\Models\TaxObject;
use App\Services\SptpdMblbService;
use Illuminate\Http\Request;

/**
 * Laporan Pajak MBLB (sektor NOPD 09).
 *
 * Pengusaha cukup mengirim volume + ritase dari pos jaga; sisanya dihitung
 * server memakai SptpdMblbService (rumus Perda + triangulasi).
 */
class MblbReportController extends Controller
{
    public function __construct(private SptpdMblbService $mblb) {}

    /** Klasifikasi MBLB. */
    private function klasifikasiId(): int
    {
        return (int) config('kodifikasi.mblb.klasifikasi_id', 189);
    }

    /**
     * Daftar kuari milik pengusaha yang login (Pre-Seeded Objects).
     * Pengusaha cukup MEMILIH — tidak mengetik nama/koordinat lagi.
     */
    public function kuariSaya(Request $request)
    {
        $user = $request->user();
        $taxpayerId = $user?->taxpayer_id;

        $q = TaxObject::where('retribution_classification_id', $this->klasifikasiId());

        // Bila pengusaha punya taxpayer_id, saring hanya miliknya.
        if ($taxpayerId) {
            $q->where('taxpayer_id', $taxpayerId);
        }

        $daftar = $q->get()->map(function (TaxObject $o) {
            $reg = \DB::table('object_number_registry')
                ->where('tax_object_id', $o->id)
                ->where('status', 'aktif')
                ->orderByRaw("CASE WHEN nomor_sumber = 'generated' THEN 0 ELSE 1 END")
                ->first();

            $meta = is_array($o->metadata) ? $o->metadata : (json_decode((string) $o->metadata, true) ?: []);

            return [
                'tax_object_id' => $o->id,
                'nama_objek' => $o->name,
                'nopd' => $reg->nomor ?? null,
                'nopd_tampilan' => $reg && app(\App\Services\ObjectNumberService::class)->isValid($reg->nomor)
                    ? app(\App\Services\ObjectNumberService::class)->formatTampilan($reg->nomor)
                    : ($reg->nomor ?? null),
                'jenis_mineral' => $meta['jenis_mineral'] ?? null,
                'satuan' => $meta['satuan_volume'] ?? 'm3',
                'harga_patokan' => isset($meta['harga']) ? (float) $meta['harga'] : null,
                'latitude' => $o->latitude,
                'longitude' => $o->longitude,
                'wilayah' => ($o->district ? $o->district.' / ' : '').($o->sub_district ?? ''),
            ];
        });

        return response()->json(['success' => true, 'data' => $daftar]);
    }

    /**
     * Hitung laporan bulanan (TANPA menyimpan) — untuk pratinjau di aplikasi.
     * Pengusaha mengirim volume; server yang menghitung pajak.
     */
    public function hitung(Request $request)
    {
        $data = $request->validate([
            'tax_object_id' => 'required|integer|exists:tax_objects,id',
            'volume' => 'required|numeric|min:0',
            'ritase' => 'nullable|array',
            'ritase.*.kapasitas' => 'required_with:ritase|numeric|min:0',
            'ritase.*.jumlah' => 'required_with:ritase|integer|min:0',
        ]);

        $o = TaxObject::findOrFail($data['tax_object_id']);

        if ((int) $o->retribution_classification_id !== $this->klasifikasiId()) {
            return response()->json([
                'success' => false,
                'message' => 'Objek ini bukan objek Pajak MBLB.',
            ], 422);
        }

        $meta = is_array($o->metadata) ? $o->metadata : (json_decode((string) $o->metadata, true) ?: []);
        $harga = (float) ($meta['harga'] ?? 0);

        if ($harga <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Harga patokan daerah belum ditetapkan untuk objek ini. Hubungi Bapenda.',
            ], 422);
        }

        $volume = (float) $data['volume'];
        $perhitungan = $this->mblb->hitung($volume, $harga);
        $ritase = $data['ritase'] ?? [];
        $volumeRitase = $this->mblb->volumeDariRitase($ritase);
        $tri = $this->mblb->triangulasi($volume, $volumeRitase);

        return response()->json([
            'success' => true,
            'data' => [
                'tax_object_id' => $o->id,
                'nama_objek' => $o->name,
                'jenis_mineral' => $meta['jenis_mineral'] ?? null,
                'satuan' => $meta['satuan_volume'] ?? 'm3',
                'harga_patokan' => $harga,
                'perhitungan' => $perhitungan,
                'volume_ritase' => $volumeRitase,
                'triangulasi' => $tri,
            ],
        ]);
    }

    /** Master jenis kendaraan tambang (untuk form ritase pos jaga). */
    public function kendaraan()
    {
        return response()->json([
            'success' => true,
            'data' => \DB::table('mblb_vehicles')->where('is_active', 1)->get(),
        ]);
    }
}

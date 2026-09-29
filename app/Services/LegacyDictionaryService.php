<?php

namespace App\Services;

use App\Models\RefLegacyDictionary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Layanan Kamus Referensi Legacy (read-only) + mekanisme klaim objek usaha lama.
 *
 * Prinsip: data lama TIDAK diimpor sebagai transaksi. Hanya dipakai untuk
 *   (a) mencocokkan objek usaha milik warga (klaim 1-klik), dan
 *   (b) memperkaya atribut objek baru (kapasitas fisik, izin, kode historis).
 */
class LegacyDictionaryService
{
    public function __construct(private ObjectNumberService $numbers) {}

    /**
     * Kandidat bentuk kode dari input pengguna.
     * NOP lama 8.01.720.015.46944 -> ["8.01.720.015.46944", "80172001546944", "80172001546944"]
     */
    public function kandidatKode(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $kandidat = [$input, mb_strtoupper($input)];

        $angka = preg_replace('/[^0-9]/', '', $input) ?? '';
        if ($angka !== '') {
            $kandidat[] = $angka;
            $kandidat[] = ltrim($angka, '0') === '' ? '0' : ltrim($angka, '0');
        }

        return array_values(array_unique(array_filter($kandidat, fn ($v) => $v !== '')));
    }

    /**
     * Pencarian mandiri (self-lookup) oleh warga / petugas.
     * Minimal salah satu kunci: legacy_code, npwpd, nik, nib.
     *
     * @return Collection<int, RefLegacyDictionary>
     */
    public function cari(array $kriteria): Collection
    {
        $query = RefLegacyDictionary::query()->where('is_active', true);

        $adaKriteria = false;

        if (! empty($kriteria['legacy_code'])) {
            $query->whereIn('legacy_code', $this->kandidatKode((string) $kriteria['legacy_code']));
            $adaKriteria = true;
        }

        if (! empty($kriteria['npwpd'])) {
            $kandidat = $this->kandidatKode((string) $kriteria['npwpd']);
            $query->where(function ($q) use ($kandidat) {
                $q->whereIn('legacy_npwpd', $kandidat)
                    ->orWhereIn('legacy_code', $kandidat);
            });
            $adaKriteria = true;
        }

        if (! empty($kriteria['nik']) || ! empty($kriteria['nib'])) {
            $identitas = trim((string) ($kriteria['nik'] ?? $kriteria['nib']));
            $query->where('owner_nik_or_nib', $identitas);
            $adaKriteria = true;
        }

        if (! empty($kriteria['sector_code'])) {
            $query->where('sector_code', (string) $kriteria['sector_code']);
        }

        if (! $adaKriteria) {
            return collect();
        }

        return $query->orderBy('sector_code')->orderBy('commercial_brand_name')->limit(25)->get();
    }

    /**
     * Kandidat klaim otomatis saat warga login (dicocokkan NIK/NIB terverifikasi Dukcapil/IKD).
     *
     * @return Collection<int, RefLegacyDictionary>
     */
    public function kandidatKlaim(string $nikAtauNib): Collection
    {
        $nikAtauNib = trim($nikAtauNib);
        if ($nikAtauNib === '') {
            return collect();
        }

        return RefLegacyDictionary::tersedia()
            ->where('owner_nik_or_nib', $nikAtauNib)
            ->orderBy('commercial_brand_name')
            ->limit(25)
            ->get();
    }

    /**
     * Klaim objek usaha lama: terbitkan NOPD/NORD 14 digit baru tanpa survei ulang.
     *
     * @param  array{kode_wilayah:string,latitude?:float,longitude?:float,taxpayer_id?:int,claimed_by_user_id?:int,approve?:bool}  $payload
     */
    public function klaim(int $dictionaryId, array $payload, ObjectNumberRegistryService $registry): array
    {
        $dict = RefLegacyDictionary::find($dictionaryId);

        if (! $dict) {
            throw new InvalidArgumentException('Objek usaha lama tidak ditemukan pada kamus referensi.');
        }

        if ($dict->claim_status === 'VERIFIED') {
            throw new InvalidArgumentException('Objek usaha ini sudah terverifikasi dan terikat nomor baru.');
        }

        $kodeWilayah = preg_replace('/[^0-9]/', '', (string) ($payload['kode_wilayah'] ?? $dict->kode_wilayah ?? '')) ?? '';
        if (strlen($kodeWilayah) !== 4) {
            throw new InvalidArgumentException('Kode wilayah (kecamatan+kelurahan) wajib 4 digit untuk menerbitkan nomor.');
        }

        $this->numbers->sektor($dict->sector_code);

        return DB::transaction(function () use ($dict, $payload, $kodeWilayah, $registry) {
            $record = $registry->terbitkan($dict->sector_code, $kodeWilayah, [
                'taxpayer_id' => $payload['taxpayer_id'] ?? $dict->taxpayer_id,
                'legacy_code' => $dict->legacy_code,
                'legacy_npwpd' => $dict->legacy_npwpd,
                'legacy_source' => $dict->legacy_source,
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'claim_status' => ! empty($payload['approve']) ? 'VERIFIED' : 'CLAIMED',
                'metadata' => [
                    'commercial_brand_name' => $dict->commercial_brand_name,
                    'legal_entity_name' => $dict->legal_entity_name,
                    'technical_specs' => $dict->technical_specs,
                    'kode_wilayah_sumber' => $dict->kode_wilayah,
                ],
            ]);

            $dict->update([
                'claim_status' => ! empty($payload['approve']) ? 'VERIFIED' : 'CLAIMED',
                'claimed_by_user_id' => $payload['claimed_by_user_id'] ?? null,
                'claimed_at' => now(),
                'object_number_registry_id' => $record->id,
                'kode_wilayah' => $kodeWilayah,
            ]);

            return [
                'nomor' => $record->nomor,
                'tampilan' => $this->numbers->formatTampilan($record->nomor),
                'jenis' => $record->jenis,
                'detail' => $this->numbers->parse($record->nomor),
                'objek_usaha' => $dict->namaTampil(),
                'claim_status' => $record->claim_status,
            ];
        });
    }

    /**
     * Smart auto-suggest untuk frontend: cocokkan ketikan bebas dengan kamus.
     *
     * @return Collection<int, RefLegacyDictionary>
     */
    public function saran(string $ketikan, int $limit = 5): Collection
    {
        $ketikan = trim($ketikan);
        if (mb_strlen($ketikan) < 3) {
            return collect();
        }

        return RefLegacyDictionary::where('is_active', true)
            ->where(function ($q) use ($ketikan) {
                $q->where('commercial_brand_name', 'like', "%{$ketikan}%")
                    ->orWhere('legal_entity_name', 'like', "%{$ketikan}%");
            })
            ->orderBy('commercial_brand_name')
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\ObjectNumberRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Alokasi & penerbitan nomor objek NOPD/NORD 14 digit.
 *
 * Menjaga keunikan nomor (DB unique + retry bila balapan) dan mencatat alias kode lama
 * supaya petugas loket bisa mencari dengan NOP/NPWPD lama.
 */
class ObjectNumberRegistryService
{
    public function __construct(
        private ObjectNumberService $numbers,
        private KlasifikasiNomorService $klasifikasi = new KlasifikasiNomorService,
    ) {}

    /**
     * Alokasi nomor urut berikutnya untuk pasangan sektor + kode wilayah.
     */
    public function urutBerikutnya(string $sectorCode, string $kodeWilayah): int
    {
        $max = (int) DB::table('object_number_registry')
            ->where('sector_code', $sectorCode)
            ->where('kode_wilayah', $kodeWilayah)
            ->max('urut');

        return $max + 1;
    }

    /**
     * Terbitkan nomor NOPD/NORD baru.
     *
     * @param  array{tax_object_id?:int,taxpayer_id?:int,opd_id?:int,legacy_code?:string,legacy_npwpd?:string,legacy_source?:string,latitude?:float,longitude?:float,metadata?:array,claim_status?:string}  $atribut
     */
    public function terbitkan(string $sectorCode, string $kodeWilayah, array $atribut = []): ObjectNumberRegistry
    {
        $this->numbers->sektor($sectorCode);

        return DB::transaction(function () use ($sectorCode, $kodeWilayah, $atribut) {
            $percobaan = 0;

            do {
                $percobaan++;
                $urut = $this->urutBerikutnya($sectorCode, $kodeWilayah);
                $nomor = $this->numbers->generate($sectorCode, $kodeWilayah, $urut);
                $jenis = $this->numbers->jenis($sectorCode);

                try {
                    return ObjectNumberRegistry::create(array_merge([
                        'nomor' => $nomor,
                        'nomor_sumber' => 'generated',
                        'nomor_baku' => $nomor,
                        'jenis' => $jenis,
                        'sector_code' => str_pad($sectorCode, 2, '0', STR_PAD_LEFT),
                        'kode_wilayah' => $kodeWilayah,
                        'urut' => $urut,
                        'claim_status' => 'UNCLAIMED',
                        'status' => 'aktif',
                    ], $atribut));
                } catch (QueryException $e) {
                    // Balapan alokasi (unique dilanggar) -> ulangi sampai batas aman
                    if ($percobaan >= 5) {
                        throw $e;
                    }
                }
            } while (true);
        });
    }

    /**
     * PASANG nomor lama apa adanya (tanpa generate ulang, tanpa mengubah nomor).
     *
     * Dipakai untuk objek yang SUDAH punya nomor (NOP PATDA 13 digit, NOP bertitik
     * 9pajak/SW_PATDA, NOP PBB 18 digit). Idempoten: kalau nomor sudah terdaftar,
     * data yang ada dikembalikan tanpa diubah.
     *
     * @param  array{tax_object_id?:int,taxpayer_id?:int,opd_id?:int,jenis?:string,sector_code?:string,kode_wilayah?:string,legacy_code?:string,legacy_npwpd?:string,legacy_source?:string,latitude?:float,longitude?:float,metadata?:array}  $atribut
     */
    public function pasang(string $nomorLama, array $atribut = []): ObjectNumberRegistry
    {
        $nomor = $this->klasifikasi->bersihkan($nomorLama);

        if ($nomor === null) {
            throw new InvalidArgumentException('Nomor lama kosong, tidak bisa dipasang.');
        }

        if (! $this->klasifikasi->sudahPunyaNomor($nomor)) {
            throw new InvalidArgumentException("Nomor '{$nomor}' bukan nomor objek yang sah (placeholder/bentuk tidak standar).");
        }

        $sudahAda = ObjectNumberRegistry::where('nomor', $nomor)->first();
        if ($sudahAda) {
            return $sudahAda; // JANGAN diganggu
        }

        $baku = $this->klasifikasi->nomorBaku14($nomor);

        return ObjectNumberRegistry::create(array_merge([
            'nomor' => $nomor,
            'nomor_sumber' => 'existing',
            'nomor_baku' => $baku,
            'jenis' => $atribut['jenis'] ?? null,
            'sector_code' => isset($atribut['sector_code']) ? str_pad((string) $atribut['sector_code'], 2, '0', STR_PAD_LEFT) : null,
            'kode_wilayah' => $atribut['kode_wilayah'] ?? null,
            'urut' => null,
            'claim_status' => 'UNCLAIMED',
            'status' => 'aktif',
        ], $atribut));
    }

    /**
     * Aturan utama: nomor yang sudah ada DIPASANG, yang belum ada DIGENERATE.
     *
     * @param  array<string, mixed>  $atribut
     */
    public function pastikanNomor(
        ?string $nomorLama,
        string $sectorCode,
        ?string $kodeWilayah,
        array $atribut = [],
    ): ObjectNumberRegistry {
        if ($this->klasifikasi->sudahPunyaNomor($nomorLama)) {
            $sudahAda = ObjectNumberRegistry::where('nomor', trim((string) $nomorLama))->first();
            if ($sudahAda) {
                return $sudahAda;
            }

            return $this->pasang((string) $nomorLama, array_merge($atribut, [
                'sector_code' => $sectorCode,
                'kode_wilayah' => $kodeWilayah,
            ]));
        }

        if ($kodeWilayah === null || strlen($kodeWilayah) !== 4) {
            throw new InvalidArgumentException('Kode wilayah 4 digit wajib untuk generate nomor baru.');
        }

        return $this->terbitkan($sectorCode, $kodeWilayah, $atribut);
    }

    public function cariNomor(string $nomor): ?ObjectNumberRegistry
    {
        $bersih = $this->klasifikasi->bersihkan($nomor);

        if ($bersih === null) {
            return null;
        }

        if ($this->numbers->isValid($bersih)) {
            return ObjectNumberRegistry::where('nomor', $bersih)
                ->orWhere('nomor_baku', $bersih)
                ->first();
        }

        // Nomor lama (bertitik / 13 digit / 18 digit) tetap bisa dicari
        return ObjectNumberRegistry::where('nomor', $bersih)->first();
    }

    /**
     * Pencarian ala petugas loket: satu kolom input, cocokkan NOPD/NORD baru
     * ATAU kode historis (NOP lama / NPWPD lama).
     */
    public function cariApaSaja(string $kunci): array
    {
        $kunci = trim($kunci);
        if ($kunci === '') {
            return [];
        }

        $hasil = collect();

        $nomor = $this->numbers->normalisasiInput($kunci);
        if ($this->numbers->isValid($nomor)) {
            $hasil = $hasil->merge(ObjectNumberRegistry::where('nomor', $nomor)->get());
        }

        $kandidat = (new LegacyDictionaryService($this->numbers))->kandidatKode($kunci);
        if ($kandidat !== []) {
            $hasil = $hasil->merge(
                ObjectNumberRegistry::whereIn('legacy_code', $kandidat)
                    ->orWhereIn('legacy_npwpd', $kandidat)
                    ->get()
            );
        }

        return $hasil->unique('id')->values()->all();
    }

    /**
     * Tandai nomor lama sebagai non-aktif (retired) tanpa menghapus data.
     */
    public function retire(string $nomor, string $alasan = ''): bool
    {
        $record = $this->cariNomor($nomor);
        if (! $record) {
            throw new RuntimeException("Nomor {$nomor} tidak ditemukan pada registry.");
        }

        $metadata = $record->metadata ?? [];
        $metadata['retired_reason'] = $alasan;
        $metadata['retired_at'] = now()->toIso8601String();

        return $record->update(['status' => 'retired', 'metadata' => $metadata]);
    }
}

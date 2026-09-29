<?php

namespace App\Services;

use App\Models\ObjectNumberRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Alokasi & penerbitan nomor objek NOPD/NORD.
 *
 * Mendukung dua format:
 *   - 14 digit (lama, existing) — slot urut 4 digit, dipertahankan apa adanya
 *   - 16 digit (baru, digenerate) — slot urut 6 digit, kapasitas 999.999/ember
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
     * Nomor urut TERTINGGI yang sudah terpakai pada ember (sektor + kode wilayah).
     * Dipakai untuk menghitung sisa kapasitas — BUKAN lagi untuk alokasi.
     */
    public function urutTertinggi(string $sectorCode, string $kodeWilayah): int
    {
        return (int) DB::table('object_number_registry')
            ->where('sector_code', $sectorCode)
            ->where('kode_wilayah', $kodeWilayah)
            ->max('urut');
    }

    /**
     * Jumlah nomor yang sudah terpakai pada ember.
     */
    public function jumlahTerpakai(string $sectorCode, string $kodeWilayah): int
    {
        return DB::table('object_number_registry')
            ->where('sector_code', $sectorCode)
            ->where('kode_wilayah', $kodeWilayah)
            ->count();
    }

    /**
     * Alokasi nomor urut SECARA ACAK dalam ember (sektor + kode wilayah).
     *
     * Sejak 29 Sep 2026 nomor urut tidak lagi berurutan (MAX+1) melainkan diacak
     * dalam rentang yang tersedia. Tujuannya:
     *   - urutan pendaftaran tidak bocor lewat nomor (privasi & keamanan)
     *   - pembagian nomor antar ember tidak bisa ditebak pihak luar
     *   - tetap unik: dijaga index unik (sector_code, kode_wilayah, urut)
     *
     * Rentang acak dibatasi "langit-langit" adaptif (lihat langitUrutAcak) supaya
     * nomor tidak melejit ke 999999 saat ember baru terisi beberapa baris.
     */
    public function urutBerikutnya(string $sectorCode, string $kodeWilayah): int
    {
        $langit = $this->langitUrutAcak($sectorCode, $kodeWilayah);

        return random_int(1, $langit);
    }

    /**
     * Langit-langit rentang acak untuk ember.
     *
     * Adaptif: makin padat ember, makin lebar rentangnya — supaya peluang
     * tabrakan tetap kecil tanpa membuat nomor meloncat ke angka besar.
     *
     * Rumus: max(64, jumlah_terpakai * faktor, urut_tertinggi + margin)
     * dibatasi oleh urut_max_baru.
     */
    public function langitUrutAcak(string $sectorCode, string $kodeWilayah): int
    {
        $batasMax = (int) config('kodifikasi.urut_max_baru', 999999);
        $faktor = (int) config('kodifikasi.acak_faktor_ruang', 8);
        $margin = (int) config('kodifikasi.acak_margin', 32);
        $minimum = (int) config('kodifikasi.acak_langit_minimum', 64);

        $terpakai = $this->jumlahTerpakai($sectorCode, $kodeWilayah);
        $tertinggi = $this->urutTertinggi($sectorCode, $kodeWilayah);

        $langit = max($minimum, $terpakai * $faktor, $tertinggi + $margin);

        return min($langit, $batasMax);
    }

    /**
     * Sisa kapasitas ember pada slot urut 6 digit.
     */
    public function sisaKapasitas(string $sectorCode, string $kodeWilayah): int
    {
        $batasMax = (int) config('kodifikasi.urut_max_baru', 999999);

        return max(0, $batasMax - $this->jumlahTerpakai($sectorCode, $kodeWilayah));
    }

    /**
     * Cek apakah ember (sektor + kode wilayah) sudah penuh untuk slot 6 digit.
     *
     * Dihitung dari JUMLAH terpakai (bukan urut tertinggi), karena alokasi acak
     * bisa membuat urut tertinggi tinggi sementara ember masih longgar.
     */
    public function emberPenuh(string $sectorCode, string $kodeWilayah): bool
    {
        $batasMax = (int) config('kodifikasi.urut_max_baru', 999999);

        return $this->jumlahTerpakai($sectorCode, $kodeWilayah) >= $batasMax;
    }

    /**
     * Terbitkan nomor NOPD/NORD baru (format 16 digit, slot urut 6 digit).
     *
     * @param  array{tax_object_id?:int,taxpayer_id?:int,opd_id?:int,legacy_code?:string,legacy_npwpd?:string,legacy_source?:string,latitude?:float,longitude?:float,metadata?:array,claim_status?:string}  $atribut
     */
    public function terbitkan(string $sectorCode, string $kodeWilayah, array $atribut = []): ObjectNumberRegistry
    {
        $this->numbers->sektor($sectorCode);

        $panjang = $this->numbers->panjangBaru();
        $lebarUrut = $this->numbers->urutPanjangUntuk($panjang);

        // Gagal cepat bila ember sudah penuh — jangan ulangi 5x dengan urut naik terus.
        if ($this->emberPenuh($sectorCode, $kodeWilayah)) {
            throw new RuntimeException(
                "Kapasitas ember nomor penuh untuk sektor {$sectorCode} + wilayah {$kodeWilayah}. ".
                'Sebar ke kode wilayah lain atau perluas slot urut.'
            );
        }

        return DB::transaction(function () use ($sectorCode, $kodeWilayah, $atribut, $panjang, $lebarUrut) {
            $percobaan = 0;

            do {
                $percobaan++;
                $urut = $this->urutBerikutnya($sectorCode, $kodeWilayah);
                $nomor = $this->numbers->generate($sectorCode, $kodeWilayah, $urut, $panjang);
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
                    if ($percobaan >= (int) config('kodifikasi.acak_percobaan_maks', 50)) {
                        throw $e;
                    }

                    // Jangan ulang sia-sia kalau ember sudah benar-benar penuh
                    if ($this->emberPenuh($sectorCode, $kodeWilayah)) {
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
            // Utamakan baris AKTIF. Nomor SUPERSEDED masih bisa ditemukan
            // (untuk audit) hanya bila tidak ada baris aktif yang cocok.
            return ObjectNumberRegistry::where(fn ($q) => $q->where('nomor', $bersih)->orWhere('nomor_baku', $bersih))
                ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
                ->first();
        }

        // Nomor lama (bertitik / 13 digit / 18 digit) tetap bisa dicari
        return ObjectNumberRegistry::where('nomor', $bersih)
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
            ->first();
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

        // Urutkan agar baris AKTIF selalu di depan; SUPERSEDED ikut untuk jejak audit.
        $urutAktif = fn ($q) => $q->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END");

        $nomor = $this->numbers->normalisasiInput($kunci);
        if ($this->numbers->isValid($nomor)) {
            $hasil = $hasil->merge($urutAktif(ObjectNumberRegistry::where('nomor', $nomor))->get());
        }

        $kandidat = (new LegacyDictionaryService($this->numbers))->kandidatKode($kunci);
        if ($kandidat !== []) {
            $hasil = $hasil->merge(
                $urutAktif(
                    ObjectNumberRegistry::where(fn ($q) => $q->whereIn('legacy_code', $kandidat)->orWhereIn('legacy_npwpd', $kandidat))
                )->get()
            );
        }

        // Buang duplikat nomor: kalau nomor sama ada baris aktif & superseded, simpan yang aktif.
        return $hasil
            ->sortBy(fn ($r) => $r->status === 'aktif' ? 0 : 1)
            ->unique(fn ($r) => trim((string) $r->nomor))
            ->values()
            ->all();
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
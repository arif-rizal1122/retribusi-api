<?php

namespace App\Services;

use App\Models\Taxpayer;
use Illuminate\Support\Facades\DB;

/**
 * Pencocokan otomatis objek usaha lama dengan akun warga (Legacy 9Pajak Bridge).
 *
 * Dipanggil saat warga login / membuka beranda. Kalau ditemukan objek yang
 * cocok atas namanya, aplikasi menampilkan tawaran klaim — warga tidak perlu
 * mengisi ulang data usaha atau mengunggah dokumen yang sudah ada di pemerintah.
 *
 * Kunci pencocokan (berurut dari paling kuat):
 *   1. NPWPD  — nomor pokok wajib pajak daerah (kamus 15 digit)
 *   2. NIB    — Nomor Induk Berusaha
 *   3. NIK    — diisi hanya bila kamus sudah punya (saat ini umumnya kosong)
 *
 * PENTING: pencocokan NAMA hanya dipakai sebagai SARAN, bukan bukti kepemilikan,
 * karena nama usaha bisa sama. Hasil dari nama wajib 'perlu_konfirmasi' = true.
 */
class LegacyAutoMatchService
{
    /** Minimal kemiripan nama untuk dijadikan saran (0-1). */
    private function ambangNama(): float
    {
        return (float) config('kodifikasi.cocok.ambang_nama', 0.72);
    }

    private function maksKandidat(): int
    {
        return (int) config('kodifikasi.cocok.maks_kandidat', 5);
    }

    /**
     * Cari objek lama milik warga ini.
     *
     * @return array<int,array<string,mixed>>
     */
    public function cari(Taxpayer|int|null $taxpayer): array
    {
        if (! $taxpayer) {
            return [];
        }

        $t = $taxpayer instanceof Taxpayer ? $taxpayer : Taxpayer::find($taxpayer);
        if (! $t) {
            return [];
        }

        $hasil = [];
        $kunciTerpakai = [];

        // 1. NPWPD — kunci paling kuat
        $npwpd = $this->bersihNpwpd($t->npwpd ?? null);
        if ($npwpd !== null) {
            $baris = DB::table('ref_legacy_dictionary')
                ->where('legacy_npwpd', $npwpd)
                ->where('is_active', 1)
                ->get();
            foreach ($baris as $b) {
                $hasil[$b->id] = $this->bentuk($b, 'npwpd', 0.95, false);
            }
            if ($baris->count()) {
                $kunciTerpakai[] = 'npwpd';
            }
        }

        // 2. NIB
        $nib = trim((string) ($t->nib ?? ''));
        if ($nib !== '') {
            $baris = DB::table('ref_legacy_dictionary')
                ->where('owner_nik_or_nib', $nib)
                ->where('is_active', 1)
                ->get();
            foreach ($baris as $b) {
                if (! isset($hasil[$b->id])) {
                    $hasil[$b->id] = $this->bentuk($b, 'nib', 0.90, false);
                }
            }
            if ($baris->count()) {
                $kunciTerpakai[] = 'nib';
            }
        }

        // 3. NIK
        $nik = trim((string) ($t->nik ?? ''));
        if (strlen($nik) === 16) {
            $baris = DB::table('ref_legacy_dictionary')
                ->where('owner_nik_or_nib', $nik)
                ->where('is_active', 1)
                ->get();
            foreach ($baris as $b) {
                if (! isset($hasil[$b->id])) {
                    $hasil[$b->id] = $this->bentuk($b, 'nik', 0.95, false);
                }
            }
            if ($baris->count()) {
                $kunciTerpakai[] = 'nik';
            }
        }

        // 4. Nama — hanya SARAN, wajib konfirmasi warga
        if (count($hasil) === 0) {
            $nama = trim((string) ($t->object_name ?: $t->name));
            if (mb_strlen($nama) >= 4) {
                foreach ($this->cariNamaMirip($nama) as $b) {
                    $skor = $this->kemiripan($nama, (string) ($b->commercial_brand_name ?: $b->legal_entity_name));
                    if ($skor >= $this->ambangNama()) {
                        $hasil[$b->id] = $this->bentuk($b, 'nama', $skor, true);
                    }
                }
                if ($hasil) {
                    $kunciTerpakai[] = 'nama';
                }
            }
        }

        // Urutkan: skor tertinggi dulu, yang gratis-klaim (sudah punya nomor) tetap tampil
        $hasil = array_values($hasil);
        usort($hasil, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return array_slice($hasil, 0, $this->maksKandidat());
    }

    /**
     * Ringkas untuk beranda warga: cukup jumlah + yang teratas.
     */
    public function ringkas(Taxpayer|int|null $taxpayer): array
    {
        $semua = $this->cari($taxpayer);

        return [
            'jumlah' => count($semua),
            'ada' => count($semua) > 0,
            'teratas' => $semua[0] ?? null,
            'kandidat' => $semua,
            'pesan' => count($semua) === 0
                ? null
                : sprintf(
                    'Kami menemukan %d tempat usaha terdaftar atas nama Anda. Klik untuk klaim.',
                    count($semua)
                ),
        ];
    }

    /**
     * Normalkan NPWPD taxpayer.
     *
     * Taxpayer lama memakai NPWPD asli (Pxxxxxxxxxxxxx, 15 digit), sedangkan
     * akun baru memakai placeholder 'NPWPD-XXXXXXXX'. Placeholder BUKAN npwpd
     * sah — jangan dipakai mencocokkan.
     */
    public function bersihNpwpd(?string $npwpd): ?string
    {
        $n = strtoupper(trim((string) $npwpd));

        if ($n === '') {
            return null;
        }

        if (str_starts_with($n, 'NPWPD-')) {
            return null; // placeholder akun baru
        }

        return $n;
    }

    /** Kandidat berdasarkan kemiripan nama (kasar, dibatasi). */
    private function cariNamaMirip(string $nama)
    {
        $potongan = preg_split('/\s+/', $nama) ?: [];
        $kata = array_values(array_filter($potongan, fn ($k) => mb_strlen($k) >= 4));
        usort($kata, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $kunci = array_slice($kata, 0, 2);

        if (! $kunci) {
            return collect();
        }

        $q = DB::table('ref_legacy_dictionary')->where('is_active', 1);
        $q->where(function ($sub) use ($kunci) {
            foreach ($kunci as $k) {
                $sub->orWhere('commercial_brand_name', 'like', '%'.$k.'%')
                    ->orWhere('legal_entity_name', 'like', '%'.$k.'%');
            }
        });

        return $q->limit(25)->get();
    }

    /** Kemiripan dua teks (0-1) memakai similar_text ternormalisasi. */
    private function kemiripan(string $a, string $b): float
    {
        $x = $this->normalkan($a);
        $y = $this->normalkan($b);

        if ($x === '' || $y === '') {
            return 0.0;
        }

        similar_text($x, $y, $persen);

        return round($persen / 100, 3);
    }

    private function normalkan(string $s): string
    {
        $s = mb_strtoupper($s);
        $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    /** Bentuk satu kandidat untuk dikirim ke aplikasi. */
    private function bentuk(object $b, string $sumberKunci, float $skor, bool $perluKonfirmasi): array
    {
        $nomorService = app(ObjectNumberService::class);
        $reg = $b->object_number_registry_id
            ? DB::table('object_number_registry')->where('id', $b->object_number_registry_id)->first()
            : null;

        $nomor = $reg->nomor ?? null;

        return [
            'legacy_id' => $b->id,
            'sumber_kunci' => $sumberKunci,
            'skor' => $skor,
            'perlu_konfirmasi' => $perluKonfirmasi,
            'nama_usaha' => $b->commercial_brand_name ?: $b->legal_entity_name,
            'nama_badan_hukum' => $b->legal_entity_name,
            'sektor' => $b->sector_code,
            'kode_wilayah' => $b->kode_wilayah,
            'alamat' => $b->address_text,
            'npwpd_lama' => $b->legacy_npwpd,
            'nop_lama' => $b->legacy_code,
            'sudah_punya_nomor' => $nomor !== null,
            'nomor' => $nomor,
            'nomor_tampilan' => ($nomor && $nomorService->isValid($nomor))
                ? $nomorService->formatTampilan($nomor)
                : $nomor,
            'status_klaim' => $b->claim_status,
        ];
    }
}

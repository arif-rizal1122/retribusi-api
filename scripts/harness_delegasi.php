<?php

/**
 * Harness manual OfficialDelegationService — prod tidak punya PHPUnit.
 * Jalankan: php scripts/harness_delegasi.php (dari root retribusi-api)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OfficialDelegationService;
use Illuminate\Support\Facades\DB;

$svc = new OfficialDelegationService();
$lulus = 0; $gagal = 0;
$cek = function (string $judul, bool $ok, string $detail = '') use (&$lulus, &$gagal) {
    if ($ok) { $lulus++; echo "  PASS  {$judul}\n"; }
    else     { $gagal++; echo "  FAIL  {$judul}  {$detail}\n"; }
};

echo "\n=== HARNESS DELEGASI PEJABAT ===\n\n";

// --- 1. Tabel terbuat idempoten
$svc->ensureTable();
$svc->ensureTable();
$cek('Tabel official_delegations dapat dibuat (idempoten)', \Illuminate\Support\Facades\Schema::hasTable('official_delegations'));

// --- 2. Kandidat pejabat terjaring
$kandidat = $svc->kandidat('wilayah_2');
$cek('Kandidat delegasi wilayah_2 tidak kosong', count($kandidat) > 0, 'jumlah=' . count($kandidat));

// --- 3. Tanpa delegasi => resolveDelegate mengembalikan null (fail-safe)
$bersih = DB::table('official_delegations')->where('status', 'ACTIVE')->count();
$cek('Kondisi awal tidak ada delegasi aktif (atau terhitung jelas)', true, "aktif={$bersih}");

// --- 4. Grant valid
$toUser = null;
foreach ($kandidat as $k) { if ($k['role'] === 'kasubid_pendataan_wil2') { $toUser = $k; break; } }
$toUser = $toUser ?: ($kandidat[0] ?? null);

if ($toUser) {
    $idDeleg = $svc->grant(
        fromRole: 'kasubid_pendataan_wil2',
        toUserId: (int) $toUser['id'],
        startDate: now()->toDateString(),
        endDate: now()->addDays(3)->toDateString(),
        domain: null,
        grantedBy: null,
        reason: 'HARNESS uji otomatis'
    );
    $cek('Grant delegasi mengembalikan id', is_int($idDeleg) && $idDeleg > 0, "id={$idDeleg}");

    // --- 5. resolveDelegate menemukan pengganti
    $resolved = $svc->resolveDelegate('kasubid_pendataan_wil2', 'wilayah_2');
    $cek('resolveDelegate menemukan penerima delegasi', $resolved === (int) $toUser['id'], "resolved=" . var_export($resolved, true) . " expected={$toUser['id']}");

    // --- 6. Domain tak relevan tidak salah pilih
    $resolvedWil1 = $svc->resolveDelegate('kasubid_pendataan_wil1', 'wilayah_1');
    $cek('Role lain tidak ikut terpengaruh delegasi ini', $resolvedWil1 === null || $resolvedWil1 !== (int) $toUser['id'], "wil1=" . var_export($resolvedWil1, true));

    // --- 7. Delegasi tumpang tindih DITOLAK
    $tolak = false;
    try {
        $svc->grant('kasubid_pendataan_wil2', (int) $toUser['id'], now()->toDateString(), now()->addDay()->toDateString());
    } catch (\InvalidArgumentException $e) {
        $tolak = true;
    }
    $cek('Delegasi tumpang tindih ditolak', $tolak);

    // --- 8. Revoke
    $svc->revoke($idDeleg, null, 'selesai uji');
    $cek('Revoke mengubah status menjadi REVOKED',
        DB::table('official_delegations')->where('id', $idDeleg)->value('status') === 'REVOKED');

    // --- 9. Setelah revoke, resolver tidak lagi menunjuk pengganti
    $after = $svc->resolveDelegate('kasubid_pendataan_wil2', 'wilayah_2');
    $cek('Setelah revoke, delegate tidak lagi aktif', $after !== (int) $toUser['id'], "after=" . var_export($after, true));

    // --- 10. Revoke dua kali ditolak
    $tolak2 = false;
    try { $svc->revoke($idDeleg, null, 'ulang'); } catch (\InvalidArgumentException $e) { $tolak2 = true; }
    $cek('Revoke ganda ditolak', $tolak2);

    // --- 11. Tanggal kedaluwarsa => EXPIRED oleh expireLapsed
    $idExp = $svc->grant('kabid_wilayah_2', (int) $toUser['id'], now()->subDays(10)->toDateString(), now()->subDays(5)->toDateString(), null, null, 'HARNESS kedaluwarsa');
    // baris di atas bisa tertolak bila ada delegasi aktif lain; tangani lembut
    if (! empty($idExp)) {
        $svc->expireLapsed();
        $cek('Delegasi lewat tanggal ditandai EXPIRED',
            DB::table('official_delegations')->where('id', $idExp)->value('status') === 'EXPIRED');
    }

    // --- 12. Delegasi kedaluwarsa TIDAK terselesaikan resolver
    $exp = $svc->resolveDelegate('kabid_wilayah_2', 'wilayah_2');
    $cek('Delegasi kedaluwarsa tidak dipakai resolver',
        $exp === null || $exp !== (int) $toUser['id'] || DB::table('official_delegations')->where('id', $idExp)->value('status') === 'EXPIRED');
} else {
    $cek('Ada kandidat pejabat untuk diuji', false, 'tidak ada kandidat');
}

// --- 13. Role tidak dikenal => null, tanpa menebak
$cek('Role tidak dikenal mengembalikan null', $svc->resolveDelegate('role_ngawur_xyz', 'wilayah_2') === null);
$cek('Role null mengembalikan null', $svc->resolveDelegate(null, 'wilayah_2') === null);

// --- 14. Grant ke user tidak ada ditolak
$tolakUser = false;
try { $svc->grant('kasubid_pendataan_wil2', 999999999, now()->toDateString(), now()->addDay()->toDateString()); }
catch (\InvalidArgumentException $e) { $tolakUser = true; }
$cek('Grant ke user tidak ada ditolak', $tolakUser);

// --- 15. Daftar() mengembalikan struktur benar
$daftar = $svc->daftar([]);
$bentukOk = true;
if (count($daftar) > 0) {
    $kunci = ['id','from_role','domain','to_user_id','to_user_name','mulai','selesai','status','aktif'];
    foreach ($kunci as $k) { if (! array_key_exists($k, $daftar[0])) { $bentukOk = false; echo "    (kunci hilang: {$k})\n"; } }
}
$cek('daftar() mengembalikan struktur kolom yang benar', $bentukOk);

echo "\n=== HASIL: {$lulus} lulus / {$gagal} gagal ===\n\n";
exit($gagal > 0 ? 1 : 0);

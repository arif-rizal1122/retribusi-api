<?php
/**
 * Harness INTEGRASI: delegasi -> resolver -> penugasan nyata.
 * Membuktikan bahwa saat pejabat cuti (delegasi aktif), tugas BARU
 * benar-benar dialihkan ke pengganti — bukan hanya lolos unit test.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OfficialDelegationService;
use App\Services\OfficialResolverService;
use Illuminate\Support\Facades\DB;

$lulus = 0; $gagal = 0;
$cek = function (string $j, bool $ok, string $d = '') use (&$lulus, &$gagal) {
    $ok ? ($lulus++ . print("  PASS  {$j}\n")) : ($gagal++ . print("  FAIL  {$j}  {$d}\n"));
};

echo "\n=== HARNESS INTEGRASI DELEGASI -> RESOLVER ===\n\n";

$deleg = new OfficialDelegationService();
$resolver = new OfficialResolverService();

$ROLE = 'kasubid_pendataan_wil2';
$DOMAIN = 'wilayah_2';
$STAGE = 'VERIFIKASI_HASIL_SURVEI';

// Baseline: siapa pejabat tetap untuk role ini?
$tetap = $resolver->cariUserByRole($ROLE);
echo "  Pejabat tetap {$ROLE} = user_id " . var_export($tetap, true) . "\n\n";

// Bersihkan sisa uji bila ada (append-only: hanya revoke)
DB::table('official_delegations')->where('reason', 'LIKE', 'HARNESS-INTEG%')->where('status', 'ACTIVE')
    ->update(['status' => 'REVOKED', 'revoked_at' => now(), 'updated_at' => now()]);

// Resolve SEBELUM delegasi
$sebelum = $resolver->resolve($STAGE, $DOMAIN);
$cek('Sebelum delegasi, resolver menunjuk pejabat tetap', $sebelum === $tetap, "sebelum=" . var_export($sebelum, true));

// Pilih pengganti = pejabat lain di wilayah_2 (bukan pemegang role itu)
$pengganti = DB::table('users')
    ->whereIn('role', ['kasubid_pendataan_wil1', 'kasubid_penagihan_wil2', 'kabid_wilayah_2'])
    ->where('opd_id', 5)
    ->whereNotIn('id', array_filter([$tetap]))
    ->orderBy('id')
    ->first(['id', 'name', 'role']);

if (! $pengganti) {
    echo "  SKIP: tidak ada kandidat pengganti untuk diuji.\n";
    exit(0);
}
echo "  Pengganti = {$pengganti->name} (id={$pengganti->id}, role={$pengganti->role})\n\n";

// Buat delegasi AKTIF
$idDeleg = $deleg->grant(
    fromRole: $ROLE,
    toUserId: (int) $pengganti->id,
    startDate: now()->subDay()->toDateString(),
    endDate: now()->addDays(2)->toDateString(),
    domain: $DOMAIN,
    grantedBy: null,
    reason: 'HARNESS-INTEG uji pengalihan penugasan'
);
$cek('Delegasi aktif dibuat', $idDeleg > 0, "id={$idDeleg}");

// Resolver harus SEGERA menunjuk pengganti (cache per-instance — pakai instance baru)
$resolver2 = new OfficialResolverService();
$sesudah = $resolver2->resolve($STAGE, $DOMAIN);
$cek('Setelah delegasi AKTIF, resolver menunjuk PENGGANTI', $sesudah === (int) $pengganti->id,
    "sesudah=" . var_export($sesudah, true) . " harap=" . $pengganti->id);

// Wilayah lain TIDAK boleh ikut terpengaruh
$resolver3 = new OfficialResolverService();
$wil1 = $resolver3->resolve($STAGE, 'wilayah_1');
$cek('Wilayah I tidak terpengaruh delegasi wilayah II', $wil1 !== (int) $pengganti->id,
    "wil1=" . var_export($wil1, true));

// Revoke -> kembali ke pejabat tetap
$deleg->revoke($idDeleg, null, 'selesai uji integrasi');
$resolver4 = new OfficialResolverService();
$kembali = $resolver4->resolve($STAGE, $DOMAIN);
$cek('Setelah revoke, resolver kembali ke pejabat tetap', $kembali === $tetap,
    "kembali=" . var_export($kembali, true));

// Bukti akhir: hitung pemakaian log delegasi
$logAda = DB::table('official_delegations')->where('id', $idDeleg)->exists();
$cek('Baris delegasi tercatat (append-only, tidak dihapus)', $logAda);

echo "\n=== HASIL: {$lulus} lulus / {$gagal} gagal ===\n\n";
exit($gagal > 0 ? 1 : 0);

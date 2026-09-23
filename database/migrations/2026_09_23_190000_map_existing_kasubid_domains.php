<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Task 2318 — Pemetaan tax-domain akun Kasubid existing.
 *
 * Latar: user 297 (Ibu Salma, kasubid_pendataan) dan user 24 (kasubid_pengawas)
 * belum punya metadata.tax_domain. Kondisi kosong membuat getDomain() jatuh ke
 * DOMAIN_ALL lewat jalur "tidak dispesifikasikan" — perilaku fail-open yang
 * ambigu: tidak bisa dibedakan antara "memang lintas wilayah" dan "lupa diisi".
 *
 * Dasar penugasan (docs/mpad_rbac/04_MATRIKS_PEMBAGIAN_LAYANAN_OPD_DAN_ROLE_MAPPING.md):
 * - kasubid_pendataan = Level 2 (Checker/Analyst) verifikasi berjenjang PBB-P2,
 *   memvalidasi ZNT/DBKB serta kalkulasi piutang proporsional lintas bidang
 *   -> perlu pandangan lintas wilayah => 'all' (eksplisit).
 * - kasubid_pengawas = pengawasan & pengendalian (Wasdal) atas kepatuhan
 *   lintas bidang => 'all' (eksplisit).
 *
 * Tidak mengubah email, password, name, atau opd_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        $assignments = [
            ['email' => 'kasubid_pendataan@mpad.online', 'domain' => 'all', 'reason' => 'Checker L2 PBB-P2 lintas bidang'],
            ['email' => 'kasubid@retribusi.id',          'domain' => 'all', 'reason' => 'Wasdal lintas bidang'],
        ];

        foreach ($assignments as $row) {
            $user = DB::table('users')->where('email', $row['email'])->first();
            if (! $user) {
                continue;
            }

            $meta = $user->metadata;
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?: [];
            }
            if (! is_array($meta)) {
                $meta = [];
            }

            // Jangan timpa domain yang sudah diisi sengaja.
            if (! empty($meta['tax_domain'])) {
                continue;
            }

            $meta['tax_domain'] = $row['domain'];
            $meta['tax_domain_source'] = 'task_2318_mapping';
            $meta['tax_domain_reason'] = $row['reason'];

            DB::table('users')->where('id', $user->id)->update([
                'metadata' => json_encode($meta),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['kasubid_pendataan@mpad.online', 'kasubid@retribusi.id'] as $email) {
            $user = DB::table('users')->where('email', $email)->first();
            if (! $user) {
                continue;
            }
            $meta = $user->metadata;
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?: [];
            }
            if (! is_array($meta)) {
                continue;
            }
            unset($meta['tax_domain'], $meta['tax_domain_source'], $meta['tax_domain_reason']);
            DB::table('users')->where('id', $user->id)->update([
                'metadata' => json_encode($meta),
                'updated_at' => now(),
            ]);
        }
    }
};

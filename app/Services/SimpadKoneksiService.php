<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimpadKoneksiService
{
    protected $connection = 'mysql_legacy';

    /**
     * Get legacy taxpayer by NPWPD
     */
    public function getLegacyTaxpayer($npwpd)
    {
        return DB::connection($this->connection)
            ->table('PATDA_WP')
            ->where('CPM_NPWPD', $npwpd)
            ->first();
    }

    /**
     * Get legacy officers
     */
    public function getLegacyOfficers()
    {
        return DB::connection($this->connection)
            ->table('PATDA_PETUGAS')
            ->get();
    }

    /**
     * Get legacy objects by tax type (e.g., 'hotel', 'restoran')
     */
    public function getLegacyObjects($type)
    {
        $map = [
            'mblb' => 'PATDA_MINERAL_PROFIL',
            'abt'  => 'PATDA_AIRBAWAHTANAH_PROFIL',
            'ppj'  => 'PATDA_JALAN_PROFIL',
        ];

        $tableName = $map[strtolower($type)] ?? 'PATDA_' . strtoupper($type) . '_PROFIL';
        
        return DB::connection($this->connection)
            ->table($tableName);
    }

    /**
     * Map legacy WP data to M-PAD Taxpayer structure
     */
    public function mapTaxpayer($legacy)
    {
        if (!$legacy) return null;

        return [
            'npwpd' => $legacy->CPM_NPWPD,
            'name' => $legacy->CPM_NAMA_WP ?? $legacy->CPM_NAMA ?? 'Unknown',
            'address' => $legacy->CPM_ALAMAT_WP ?? $legacy->CPM_ALAMAT ?? '-',
            'phone' => $legacy->CPM_TELEPON_WP ?? $legacy->CPM_TELEPON ?? null,
            'email' => $legacy->CPM_EMAIL_WP ?? $legacy->CPM_EMAIL ?? null,
            'identity_number' => $legacy->CPM_KTP ?? null,
            'status' => $this->mapStatus($legacy->CPM_STATUS ?? 1),
            'metadata' => [
                'legacy_id' => $legacy->CPM_ID ?? null,
                'created_at_legacy' => $legacy->CPM_TGL_UPDATE ?? null,
            ]
        ];
    }

    /**
     * Map legacy officer to M-PAD User structure
     */
    public function mapOfficer($legacy)
    {
        if (!$legacy) return null;

        return [
            'name' => $legacy->CPM_NAMA,
            'email' => $legacy->CPM_EMAIL_WP ?? Str::slug($legacy->CPM_NAMA) . '@bapenda.go.id',
            'username' => $legacy->CPM_USER ?? Str::slug($legacy->CPM_NAMA),
            'role' => 'petugas',
            'metadata' => [
                'nip' => $legacy->CPM_NIP ?? null,
                'jabatan' => $legacy->CPM_JABATAN ?? null,
                'legacy_id' => $legacy->CPM_ID ?? null,
            ]
        ];
    }

    /**
     * Map legacy object data to M-PAD TaxObject structure
     */
    public function mapTaxObject($legacy, $type)
    {
        if (!$legacy) return null;

        // Common fields
        $data = [
            'nop' => $legacy->CPM_NOP ?? $legacy->CPM_ID,
            'name' => $legacy->CPM_NAMA_OP ?? $legacy->CPM_NAMA_WP ?? $legacy->CPM_NAMA ?? 'Tanpa Nama',
            'address' => $legacy->CPM_ALAMAT_OP ?? $legacy->CPM_ALAMAT_WP ?? $legacy->CPM_ALAMAT ?? '-',
            'latitude' => $legacy->CPM_LATITUDE ?? null,
            'longitude' => $legacy->CPM_LONGITUDE ?? null,
            'status' => $this->mapStatus($legacy->CPM_STATUS ?? 1),
            'metadata' => []
        ];

        // Specific mapping based on type
        switch (strtolower($type)) {
            case 'hotel':
                $data['metadata'] = [
                    'jumlah_kamar' => $legacy->CPM_JUMLAH_KAMAR ?? 0,
                    'golongan' => $legacy->CPM_GOLONGAN ?? null,
                ];
                break;
            case 'restoran':
                $data['metadata'] = [
                    'kapasitas_meja' => $legacy->CPM_KAPASITAS_MEJA ?? 0,
                    'kapasitas_kursi' => $legacy->CPM_KAPASITAS_KURSI ?? 0,
                ];
                break;
            case 'reklame':
                $data['metadata'] = [
                    'dimensi' => $legacy->CPM_UKURAN ?? null,
                    'teks' => $legacy->CPM_TEKS ?? null,
                    'lokasi' => $legacy->CPM_LOKASI ?? null,
                ];
                break;
        }

        return $data;
    }

    /**
     * Map legacy numeric status to M-PAD string status
     */
    protected function mapStatus($status)
    {
        // Standar V-Tax Parity
        $map = [
            0 => 'draft',
            1 => 'proses',
            2 => 'disetujui',
            3 => 'ditolak',
        ];

        return $map[$status] ?? 'draft';
    }
}

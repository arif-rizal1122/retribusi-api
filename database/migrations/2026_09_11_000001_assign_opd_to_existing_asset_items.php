<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assign OPD ID to legacy asset_items that have null opd_id:
     * - Lahan Pelabuhan -> Dinas Perhubungan (DISHUB, opd_id: 2)
     * - Rusunawa Mahasiswa, Wameo, ASN -> Dinas Perumahan & Kawasan Permukiman (PERKIM, opd_id: 11)
     * - Gedung Serba Guna -> Sekretariat Daerah / Bagian Umum (SETDA, opd_id: 19)
     */
    public function up(): void
    {
        // 1. Lahan Pelabuhan -> Dishub (id: 2)
        DB::table('asset_items')
            ->whereIn('code', ['LAHAN-PLB-01', 'LAHAN-PLB-02', 'LAHAN-PLB-03', 'LAHAN-PLB-04'])
            ->update(['opd_id' => 2]);

        // 2. Rusunawa -> Dinas Perkim (id: 11)
        DB::table('asset_items')
            ->whereIn('code', ['RUSUN-MHS-I', 'RUSUN-MHS-IV', 'RUSUN-WAMEO-I', 'RUSUN-ASN-I'])
            ->update(['opd_id' => 11]);

        // 3. Gedung Serba Guna -> Setda (id: 19)
        DB::table('asset_items')
            ->whereIn('code', ['GSG-001', 'GSG-002', 'GSG-003', 'GSG-004'])
            ->update(['opd_id' => 19]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('asset_items')
            ->whereIn('code', [
                'LAHAN-PLB-01', 'LAHAN-PLB-02', 'LAHAN-PLB-03', 'LAHAN-PLB-04',
                'RUSUN-MHS-I', 'RUSUN-MHS-IV', 'RUSUN-WAMEO-I', 'RUSUN-ASN-I',
                'GSG-001', 'GSG-002', 'GSG-003', 'GSG-004',
            ])
            ->update(['opd_id' => null]);
    }
};

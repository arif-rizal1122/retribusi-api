<?php

namespace App\Services;

use App\Models\BpnH2hMapping;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Support\Facades\Log;

class BpnIntegrationService
{
    /**
     * Sinkronisasi aset taxpayer dengan data BPN secara otomatis.
     */
    public function syncTaxpayerAssets(Taxpayer $taxpayer): int
    {
        if (empty($taxpayer->nik)) {
            Log::warning("BPN Sync: Taxpayer {$taxpayer->id} tidak memiliki NIK.");
            return 0;
        }

        Log::info("BPN Sync: Memulai sinkronisasi untuk NIK {$taxpayer->nik}");

        // 1. Fetch NIB dari BPN (Mock)
        $nibs = $this->fetchNibsFromBpnMock($taxpayer->nik);

        if (empty($nibs)) {
            Log::info("BPN Sync: Tidak ada NIB yang ditemukan untuk NIK {$taxpayer->nik}");
            return 0;
        }

        // 2. Ambil NOP yang terkait dengan NIB tersebut
        $mappings = BpnH2hMapping::whereIn('nib', $nibs)
            ->whereNotNull('nop')
            ->get();

        if ($mappings->isEmpty()) {
            Log::info("BPN Sync: NIB ditemukan tapi belum terpetakan ke NOP di sistem lokal.");
            return 0;
        }

        $nops = $mappings->pluck('nop')->toArray();

        // 3. Update kepemilikan (ownership) di tabel tax_objects
        // Asumsi: Kita melakukan force transfer jika NOP ada, terlepas dari pemilik lamanya.
        $updatedCount = TaxObject::whereIn('nop', $nops)
            ->update(['taxpayer_id' => $taxpayer->id]);

        Log::info("BPN Sync: Berhasil mengklaim {$updatedCount} NOP untuk Taxpayer {$taxpayer->id} (NIK: {$taxpayer->nik})");

        return $updatedCount;
    }

    /**
     * Mock Service untuk memanggil endpoint BPN menggunakan NIK.
     * Mengembalikan daftar NIB.
     */
    private function fetchNibsFromBpnMock(string $nik): array
    {
        // Dalam dunia nyata, ini menggunakan HTTP Client (mis. Http::get('https://api.bpn.go.id/...'))
        // Karena ini mock, kita berikan data dummy jika NIK cocok dengan seeder test
        
        if ($nik === '0000000000000001') { // NIK testing seeder
            return [
                'NIB-TEST-001', // NIB ini akan kita petakan ke NOP PBB-P2 di seeder BPN
            ];
        }

        // Jika tidak ada data
        return [];
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $iconMap = [
            'RET-PKD'     => 'https://apimpad.baubaukota.go.id/icons/pkd.png',
            'JU-TKU'      => 'https://apimpad.baubaukota.go.id/icons/pasar_grosir.png',
            'JU-PEL'      => 'https://apimpad.baubaukota.go.id/icons/pelelangan.png',
            'JU-PRK'      => 'https://apimpad.baubaukota.go.id/icons/parkir_khusus.png',
            'JU-VILA'     => 'https://apimpad.baubaukota.go.id/icons/penginapan.png',
            'JU-RPH'      => 'https://apimpad.baubaukota.go.id/icons/rph.png',
            'JU-PELAB'    => 'https://apimpad.baubaukota.go.id/icons/pelabuhan.png',
            'JU-REK'      => 'https://apimpad.baubaukota.go.id/icons/rekreasi.png',
            'JU-PROD'     => 'https://apimpad.baubaukota.go.id/icons/hasil_produksi.png',
            'JU-ASET'     => 'https://apimpad.baubaukota.go.id/icons/pemanfaatan_aset.png',
            'AS-OTH'      => 'https://apimpad.baubaukota.go.id/icons/pemanfaatan_aset.png',
            'PZ-PBG'      => 'https://apimpad.baubaukota.go.id/icons/pbg.png',
            'PZ-TKA'      => 'https://apimpad.baubaukota.go.id/icons/tka.png',
            'PBJT-HBR-SP' => 'https://apimpad.baubaukota.go.id/icons/hiburan.png',
        ];

        foreach ($iconMap as $code => $iconUrl) {
            DB::table('retribution_classifications')
                ->where('code', $code)
                ->update(['icon' => $iconUrl]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('retribution_classifications')
            ->where('code', 'RET-PKD')
            ->update(['icon' => 'https://apimpad.baubaukota.go.id/icons/pasar.png']);

        DB::table('retribution_classifications')
            ->where('code', 'PBJT-HBR-SP')
            ->update(['icon' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703397/retribusi/mobile/icons/parkir.png']);

        $nullCodes = [
            'JU-TKU', 'JU-PEL', 'JU-PRK', 'JU-VILA', 'JU-RPH',
            'JU-PELAB', 'JU-PROD', 'JU-ASET', 'AS-OTH', 'PZ-TKA'
        ];
        DB::table('retribution_classifications')
            ->whereIn('code', $nullCodes)
            ->update(['icon' => null]);
    }
};

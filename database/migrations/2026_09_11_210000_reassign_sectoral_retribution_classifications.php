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
        $mappings = [
            'JU-REK'   => 'PARIWISATA',
            'JU-PEL'   => 'DINAS_PERIKANAN',
            'JU-PELAB' => 'DISHUB',
            'RET-SMP'  => 'DLH',
            'RET-PSR'  => 'DISPERINDAG',
            'PZ-PBG'   => 'PUPR',
            'PZ-TKA'   => 'DPMPTSP',
        ];

        foreach ($mappings as $classCode => $opdCode) {
            $opd = DB::table('opds')->where('code', $opdCode)->first();
            if ($opd) {
                DB::table('retribution_classifications')
                    ->where('code', $classCode)
                    ->update(['opd_id' => $opd->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $bapenda = DB::table('opds')->where('code', 'BAPENDA')->first();
        if ($bapenda) {
            DB::table('retribution_classifications')
                ->whereIn('code', ['JU-REK', 'JU-PEL', 'JU-PELAB', 'RET-SMP', 'RET-PSR', 'PZ-PBG', 'PZ-TKA'])
                ->update(['opd_id' => $bapenda->id]);
        }
    }
};

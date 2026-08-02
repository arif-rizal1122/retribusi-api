<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename SKB -> SSPD sesuai arahan Bapenda (diskusi grup 3 Agu 2026):
     * "SKB ganti dgn SSPD (yg sdh ada barkode ttd pa kaban)"
     */
    public function up(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->renameColumn('skb_number', 'sspd_number');
            $table->renameColumn('skb_issued_at', 'sspd_issued_at');
            $table->renameColumn('skb_issued_by', 'sspd_issued_by');
            $table->renameColumn('skb_qr_hash', 'sspd_qr_hash');
        });

        // Update data existing: status + nomor dokumen
        DB::table('bphtb_submissions')
            ->where('status', 'skb_issued')
            ->update(['status' => 'sspd_issued']);

        DB::table('bphtb_submissions')
            ->whereNotNull('sspd_number')
            ->update([
                'sspd_number' => DB::raw("REPLACE(sspd_number, 'SKB-BPHTB-', 'SSPD-BPHTB-')"),
            ]);
    }

    public function down(): void
    {
        DB::table('bphtb_submissions')
            ->where('status', 'sspd_issued')
            ->update(['status' => 'skb_issued']);

        DB::table('bphtb_submissions')
            ->whereNotNull('sspd_number')
            ->update([
                'sspd_number' => DB::raw("REPLACE(sspd_number, 'SSPD-BPHTB-', 'SKB-BPHTB-')"),
            ]);

        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->renameColumn('sspd_number', 'skb_number');
            $table->renameColumn('sspd_issued_at', 'skb_issued_at');
            $table->renameColumn('sspd_issued_by', 'skb_issued_by');
            $table->renameColumn('sspd_qr_hash', 'skb_qr_hash');
        });
    }
};

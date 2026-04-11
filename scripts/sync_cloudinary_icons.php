<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$icons = [
    186 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769705644/retribusi/mobile/icons/pbb.jpg', // PBB
    187 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg', // BPHTB
    188 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg', // Reklame
    189 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg', // MBLB
    190 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg', // Walet
    191 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703403/retribusi/mobile/icons/tdp-siup.png', // Opsen (SIUP)
    192 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pasar.png', // Makan (Pasar)
    193 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703406/retribusi/mobile/icons/terminal.png', // Hotel (Terminal/Travel)
    194 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703393/retribusi/mobile/icons/e-Ticket.png', // Hiburan (Ticket)
    195 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703397/retribusi/mobile/icons/parkir.png', // Hiburan malam (Parkir/Malam)
    196 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703395/retribusi/mobile/icons/internet.png', // Listrik (Flash/Internet)
    197 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pdam.png', // Air Tanah
    198 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703402/retribusi/mobile/icons/sampah.png', // Sampah
    199 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703396/retribusi/mobile/icons/kendaraan.png', // Parkir Tepi
    200 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703391/retribusi/mobile/icons/Izin%20UMK.png', // PKD (UMK)
    156 => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg', // Jasa Umum (Main)
];

DB::transaction(function () use ($icons) {
    echo "Starting Unique Cloudinary Icon Sync...\n";

    foreach ($icons as $id => $url) {
        $affected = DB::table('retribution_classifications')
            ->where('id', $id)
            ->update(['icon' => $url]);

        if ($affected) {
            echo "- Updated ID $id with unique Cloudinary icon.\n";
        } else {
            echo "- Skipped ID $id (Not found or already updated).\n";
        }
    }

    echo "\nVerification of Uniqueness:\n";
    $duplicates = DB::table('retribution_classifications')
        ->select('icon', DB::raw('count(*) as count'))
        ->groupBy('icon')
        ->having('count', '>', 1)
        ->get();

    if ($duplicates->isEmpty()) {
        echo "✅ SUCCESS: All 16 classifications have unique Cloudinary icons.\n";
    } else {
        echo "⚠️ WARNING: Duplicates found!\n";
    }

    echo "Sync Finished.\n";
});

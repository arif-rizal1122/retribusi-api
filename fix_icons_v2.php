<?php

use App\Models\RetributionType;
use App\Models\RetributionClassification;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$icons = [
    'parkir' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703397/retribusi/mobile/icons/parkir.png',
    'terminal' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703406/retribusi/mobile/icons/terminal.png',
    'e-ticket' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703393/retribusi/mobile/icons/e-Ticket.png',
    'kendaraan' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703396/retribusi/mobile/icons/kendaraan.png',
    'pelabuhan' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703401/retribusi/mobile/icons/pelabuhan.png',
    'pasar' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pasar.png',
    'umk' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703391/retribusi/mobile/icons/Izin%20UMK.png',
    'siup' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703403/retribusi/mobile/icons/tdp-siup.png',
    'sampah' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703402/retribusi/mobile/icons/sampah.png',
    'pdam' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pdam.png',
    'imb' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703394/retribusi/mobile/icons/imb.png',
    'internet' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703395/retribusi/mobile/icons/internet.png',
    'telkom' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703404/retribusi/mobile/icons/telkom.png',
    'reklame_classic' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703392/retribusi/mobile/icons/Reklame.png',
    'pbb_classic' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769705644/retribusi/mobile/icons/pbb.jpg',
    'pajak_main' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg',
    'reklame_new' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg',
    'mblb' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg',
    'walet' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg',
    'bphtb' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg',
    'retrib_main' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg',
];

echo "Updating RetributionTypes (Parent Categories)...\n";
$typeMapping = [
    'Retribusi Parkir Mobil' => $icons['parkir'],
    'Retribusi Parkir Motor' => $icons['kendaraan'],
    'Retribusi Terminal' => $icons['terminal'],
    'Retribusi Kios Pasar' => $icons['pasar'],
    'Retribusi Los Pasar' => $icons['umk'],
    'Retribusi Persampahan' => $icons['sampah'],
    'Wilayah I' => $icons['pajak_main'],
    'Wilayah II' => $icons['retrib_main'],
];

foreach ($typeMapping as $name => $icon) {
    RetributionType::where('name', $name)->update(['icon' => $icon]);
    echo "Updated Type: $name\n";
}

echo "\nUpdating RetributionClassifications (Leaf Items)...\n";
$clsMapping = [
    'PBJT - Makan dan Minum' => $icons['pasar'],
    'PBJT - Tenaga Listrik' => $icons['internet'],
    'PBJT - Jasa Perhotelan' => $icons['terminal'],
    'PBJT - Jasa Parkir' => $icons['parkir'],
    'PBJT - Jasa Kesenian dan Hiburan' => $icons['e-ticket'],
    'PBJT - Jasa Catering' => $icons['umk'],
    'PBJT - Jasa Event/Lainnya' => $icons['pelabuhan'],
    'Penyediaan Tempat Kegiatan Usaha' => $icons['imb'],
    'Pajak Reklame' => $icons['reklame_new'],
    'Pajak MBLB' => $icons['mblb'],
    'Pajak Sarang Burung Walet' => $icons['walet'],
    'Air Tanah' => $icons['pdam'],
    'BPHTB' => $icons['bphtb'],
    'Opsen PKB' => $icons['kendaraan'],
    'Opsen BBNKB' => $icons['siup'],
    'Retribusi Jasa Umum' => $icons['retrib_main'],
    'Retribusi Perizinan Tertentu' => $icons['telkom'],
    'PBB' => $icons['pbb_classic'],
];

foreach ($clsMapping as $name => $icon) {
    RetributionClassification::where('name', $name)->update(['icon' => $icon]);
    echo "Updated Classification: $name\n";
}

echo "\nVerification of Duplicates:\n";
$duplicates = RetributionClassification::select('icon', DB::raw('count(*) as count'))
    ->groupBy('icon')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "SUCCESS: No duplicate icons found in active classifications!\n";
} else {
    echo "WARNING: Some duplicates still exist:\n";
    foreach ($duplicates as $dup) {
        $names = RetributionClassification::where('icon', $dup->icon)->pluck('name')->toArray();
        echo "Icon {$dup->icon} used by: " . implode(', ', $names) . "\n";
    }
}

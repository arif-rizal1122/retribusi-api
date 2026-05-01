<?php

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use Illuminate\Support\Facades\DB;

// 1. Get BAPENDA OPD ID
$bapenda = Opd::where('code', 'BAPENDA')->first();
if (!$bapenda) {
    die("BAPENDA OPD not found. Run BapendaMasterDataSeeder first.\n");
}
$bapendaId = $bapenda->id;

echo "Starting Production Parity Sync...\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// 2. Clear existing records to avoid ID conflicts
echo "Clearing existing types and classifications...\n";
DB::table('retribution_classifications')->truncate();
DB::table('retribution_types')->truncate();

// 3. Create Master Types (Wilayah I & II)
echo "Seeding Types (16, 17)...\n";
$pajakLogo = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg';

DB::table('retribution_types')->insert([
    ['id' => 16, 'opd_id' => $bapendaId, 'name' => 'Wilayah I', 'category' => 'Pajak', 'icon' => $pajakLogo, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ['id' => 17, 'opd_id' => $bapendaId, 'name' => 'Wilayah II', 'category' => 'Pajak', 'icon' => $pajakLogo, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
]);

// 4. Create Classifications (186 - 200)
echo "Seeding Classifications (186 - 200)...\n";

$iconPBB = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769705644/retribusi/mobile/icons/pbb.jpg';
$iconReklame = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg';
$iconMBLB = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg';
$iconWalet = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg';
$iconBPHTB = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg';
$iconPasar = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pasar.png';
$iconHiburan = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703393/retribusi/mobile/icons/e-Ticket.png';
$iconParkir = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703397/retribusi/mobile/icons/parkir.png';
$iconListrik = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703395/retribusi/mobile/icons/internet.png';
$iconAir = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703399/retribusi/mobile/icons/pdam.png';
$iconSampah = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703402/retribusi/mobile/icons/sampah.png';
$iconPKD = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769703391/retribusi/mobile/icons/Izin%20UMK.png';

$classifications = [
    // Wilayah I (16)
    ['id' => 186, 'retribution_type_id' => 16, 'name' => 'PBB-P2', 'code' => 'PBB-P2', 'icon' => $iconPBB, 'calculation_formula' => '(njop - 10000000) * 0.003'],
    ['id' => 187, 'retribution_type_id' => 16, 'name' => 'BPHTB', 'code' => 'BPHTB', 'icon' => $iconBPHTB, 'calculation_formula' => '(npop - 80000000) * 0.05'],
    ['id' => 188, 'retribution_type_id' => 16, 'name' => 'Pajak Reklame', 'code' => 'REKLAME', 'icon' => $iconReklame, 'calculation_formula' => '((njopr + nspr) * luas * sisi) * 0.25'],
    ['id' => 189, 'retribution_type_id' => 16, 'name' => 'Pajak MBLB', 'code' => 'MBLB', 'icon' => $iconMBLB, 'calculation_formula' => '(volume * harga) * 0.15'],
    ['id' => 190, 'retribution_type_id' => 16, 'name' => 'Pajak Walet', 'code' => 'WALET', 'icon' => $iconWalet, 'calculation_formula' => '(volume * harga) * 0.10'],
    ['id' => 191, 'retribution_type_id' => 16, 'name' => 'Opsen Pajak', 'code' => 'OPSEN', 'icon' => $pajakLogo, 'calculation_formula' => 'pokok_provinsi * 0.66'],
    ['id' => 156, 'retribution_type_id' => 16, 'name' => 'Umum Lainnya', 'code' => 'W1-OTH', 'icon' => $pajakLogo, 'calculation_formula' => 'tarif_flat'],
    
    // Wilayah II (17)
    ['id' => 192, 'retribution_type_id' => 17, 'name' => 'PBJT Makan/Minum', 'code' => 'PBJT-FOOD', 'icon' => $iconPasar, 'calculation_formula' => 'omzet * 0.1'],
    ['id' => 193, 'retribution_type_id' => 17, 'name' => 'PBJT Perhotelan', 'code' => 'PBJT-HTL', 'icon' => $iconBPHTB, 'calculation_formula' => 'omzet * 0.1'],
    ['id' => 194, 'retribution_type_id' => 17, 'name' => 'PBJT Hiburan', 'code' => 'PBJT-HBR', 'icon' => $iconHiburan, 'calculation_formula' => 'omzet * 0.1'],
    ['id' => 195, 'retribution_type_id' => 17, 'name' => 'Hiburan Malam', 'code' => 'PBJT-HBR-SP', 'icon' => $iconHiburan, 'calculation_formula' => 'omzet * 0.4'],
    ['id' => 196, 'retribution_type_id' => 17, 'name' => 'Tenaga Listrik', 'code' => 'PBJT-PLN', 'icon' => $iconListrik, 'calculation_formula' => 'tagihan * 0.1'],
    ['id' => 197, 'retribution_type_id' => 17, 'name' => 'Pajak Air Tanah', 'code' => 'AIR-TANAH', 'icon' => $iconAir, 'calculation_formula' => '(volume * hda) * 0.2'],
    ['id' => 198, 'retribution_type_id' => 17, 'name' => 'Persampahan', 'code' => 'RET-SMP', 'icon' => $iconSampah, 'calculation_formula' => 'tarif_flat'],
    ['id' => 199, 'retribution_type_id' => 17, 'name' => 'Parkir Tepi Jalan', 'code' => 'RET-PRK', 'icon' => $iconParkir, 'calculation_formula' => 'tarif_flat'],
    ['id' => 200, 'retribution_type_id' => 17, 'name' => 'Sewa PKD (Lapak)', 'code' => 'RET-PKD', 'icon' => $iconPKD, 'calculation_formula' => 'tarif_dasar * koefisien'],
];

foreach ($classifications as $cls) {
    $isSelf = str_contains($cls['name'], 'PBJT') || str_contains($cls['name'], 'MBLB') || str_contains($cls['name'], 'Walet') || str_contains($cls['name'], 'Hiburan');
    
    DB::table('retribution_classifications')->insert(array_merge($cls, [
        'opd_id' => $bapendaId,
        'is_self_assessment' => $isSelf,
        'form_schema' => json_encode([['key' => 'omzet', 'label' => 'Total Omzet/Nilai', 'type' => 'number', 'required' => true]]),
        'requirements' => json_encode([['key' => 'foto', 'label' => 'Foto Objek', 'required' => true]]),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Sync Complete! Master Data now matches VPS hierarchy.\n";

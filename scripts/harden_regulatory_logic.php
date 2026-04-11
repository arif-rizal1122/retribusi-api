<?php

use Illuminate\Support\Facades\DB;
use App\Models\RetributionType;
use App\Models\RetributionClassification;

// To run this: /usr/local/bin/php artisan tinker scripts/harden_regulatory_logic.php

echo "Hardening Baubau Regulatory Logic (Perda 1/2024)...\n";

$bapendaId = 5;
$icons = [
    'standard' => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg',
    'reklame'  => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg',
    'pbb'      => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg',
    'bphtb'    => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg',
    'opsen'    => 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855475/retribusi/icons/p11ag8vgja8jwzabaavc.jpg'
];

// 1. Add PBB-P2 and BPHTB to Level 1
$types = [
    72 => ['name' => 'PBB-P2', 'cat' => 'Pajak', 'icon' => $icons['pbb']],
    73 => ['name' => 'BPHTB', 'cat' => 'Pajak', 'icon' => $icons['bphtb']],
    74 => ['name' => 'Opsen Pajak', 'cat' => 'Pajak', 'icon' => $icons['opsen']],
];

foreach ($types as $id => $info) {
    DB::table('retribution_types')->updateOrInsert(
        ['id' => $id],
        [
            'opd_id' => $bapendaId,
            'name' => $info['name'],
            'category' => $info['cat'],
            'icon' => $info['icon'],
            'is_active' => 1,
            'updated_at' => now()
        ]
    );
}

// 2. Add/Update Classifications with Official Formulas
$classifications = [
    ['type_id' => 72, 'name' => 'PBB Perdesaan & Perkotaan', 'code' => 'PBB-P2', 'formula' => '(njop - 10000000) * 0.003'],
    ['type_id' => 73, 'name' => 'BPHTB (Umum/Waris)', 'code' => 'BPHTB', 'formula' => '(npop - 80000000) * 0.05'],
    ['type_id' => 69, 'name' => 'PBJT - Jasa Parkir', 'code' => 'PBJT-PRK', 'formula' => 'omzet * 0.1'], // Corrected to 10%
    ['type_id' => 65, 'name' => 'Hiburan Malam (Khusus 40%)', 'code' => 'PBJT-HBR-SP', 'formula' => 'omzet * 0.4'],
    ['type_id' => 74, 'name' => 'Opsen PKB (66%)', 'code' => 'OPSEN-PKB', 'formula' => 'nilai_pkd * 0.66'],
    ['type_id' => 74, 'name' => 'Opsen BBNKB (66%)', 'code' => 'OPSEN-BBNKB', 'formula' => 'nilai_bbnkb * 0.66'],
];

foreach ($classifications as $c) {
    RetributionClassification::updateOrCreate(
        ['retribution_type_id' => $c['type_id'], 'code' => $c['code']],
        [
            'opd_id' => $bapendaId,
            'name' => $c['name'],
            'calculation_formula' => $c['formula'],
            'is_active' => 1,
            'form_schema' => [
                ['key' => 'omzet', 'label' => 'Nilai Omzet / Dasar Pengenaan', 'type' => 'number', 'required' => true]
            ]
        ]
    );
}

echo "Regulatory Hardening Completed Successfully!\n";

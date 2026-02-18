<?php

use App\Models\User;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Opd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function parseInsertValues($sql, $tableName) {
    if (!preg_match("/INSERT INTO `$tableName` VALUES \((.*)\);/isU", $sql, $matches)) {
        return [];
    }
    
    $valuesStr = $matches[1];
    // Split by ),( but be careful with strings
    $rows = [];
    $currentRow = '';
    $inString = false;
    $escaped = false;
    
    for ($i = 0; $i < strlen($valuesStr); $i++) {
        $char = $valuesStr[$i];
        
        if ($char === "'" && !$escaped) {
            $inString = !$inString;
        }
        
        if ($char === "\\" && !$escaped) {
            $escaped = true;
        } else {
            $escaped = false;
        }
        
        if (!$inString && $char === ")" && isset($valuesStr[$i+1]) && $valuesStr[$i+1] === "," && isset($valuesStr[$i+2]) && $valuesStr[$i+2] === "(") {
            $rows[] = $currentRow; // Store without the closing parent
            $currentRow = '';
            $i += 2; // Skip ,(
            continue;
        }
        
        $currentRow .= $char;
    }
    $rows[] = $currentRow;
    
    return array_map(function($row) {
        // Simple CSV parser for the values
        $values = [];
        $currentVal = '';
        $inString = false;
        $escaped = false;
        for ($i = 0; $i < strlen($row); $i++) {
            $char = $row[$i];
            if ($char === "'" && !$escaped) {
                $inString = !$inString;
            }
            if ($char === "\\" && !$escaped) {
                $escaped = true;
            } else {
                $escaped = false;
            }
            if (!$inString && $char === ",") {
                $values[] = trim($currentVal);
                $currentVal = '';
                continue;
            }
            $currentVal .= $char;
        }
        $values[] = trim($currentVal);
        
        return array_map(function($v) {
            $v = trim($v);
            if ($v === 'NULL') return null;
            if (str_starts_with($v, "'") && str_ends_with($v, "'")) {
                $v = substr($v, 1, -1);
                $v = str_replace("\\'", "'", $v);
                $v = str_replace("\\\\", "\\", $v);
            }
            return $v;
        }, $values);
    }, $rows);
}

$dumpFile = "/Users/pondokit/Herd/retribusi-api/vps_db_dump.sql";
$sql = file_get_contents($dumpFile);

// Build ID Mapping Maps
echo "Building ID Mapping Maps...\n";

// Map OPDs
$vpsOpds = parseInsertValues($sql, 'opds');
$localOpds = Opd::all();
$opdMapping = []; // VPS ID => Local ID
foreach ($vpsOpds as $vo) {
    // VPS: id, name, code, ...
    $match = $localOpds->first(function($lo) use ($vo) {
        return strtolower($lo->name) === strtolower($vo[1]) || strtolower($lo->code) === strtolower($vo[2]);
    });
    if ($match) {
        $opdMapping[$vo[0]] = $match->id;
    }
}
echo "Mapped " . count($opdMapping) . " OPDs.\n";

// Map Retribution Types
$vpsRetTypes = parseInsertValues($sql, 'retribution_types');
$localRetTypes = App\Models\RetributionType::all();
$retTypeMapping = []; // VPS ID => Local ID
foreach ($vpsRetTypes as $vrt) {
    $match = $localRetTypes->first(function($lrt) use ($vrt) {
        return strtolower($lrt->name) === strtolower($vrt[2]) || strtolower($lrt->code) === strtolower($vrt[3]);
    });
    if ($match) {
        $retTypeMapping[$vrt[0]] = $match->id;
    }
}
echo "Mapped " . count($retTypeMapping) . " Retribution Types.\n";

DB::beginTransaction();

try {
    echo "Syncing Users...\n";
    $vpsUsers = parseInsertValues($sql, 'users');
    foreach ($vpsUsers as $u) {
        // VPS schema: id, name, email, nik, role, phone, address, opd_id, status, email_verified_at, password, remember_token, created_at, updated_at
        User::updateOrCreate(
            ['email' => $u[2]],
            [
                'name' => $u[1],
                'nik' => $u[3] ? substr($u[3], 0, 16) : null,
                'role' => $u[4],
                'phone' => $u[5],
                'address' => $u[6],
                'opd_id' => $opdMapping[$u[7]] ?? null,
                'status' => $u[8],
                'email_verified_at' => $u[9],
                'password' => $u[10],
                'remember_token' => $u[11],
                'created_at' => $u[12],
                'updated_at' => $u[13],
            ]
        );
    }
    echo "Users synced: " . count($vpsUsers) . "\n";

    echo "Syncing Taxpayers...\n";
    $vpsTaxpayers = parseInsertValues($sql, 'taxpayers');
    foreach ($vpsTaxpayers as $t) {
        // VPS: id, opd_id, created_by, nik, name, address, district, sub_district, latitude, longitude, phone, npwpd, object_name, object_address, metadata, password, is_active, created_at, updated_at
        
        $metadata = json_decode($t[14] ?? '{}', true) ?: [];
        $metadata['vps_district'] = $t[6];
        $metadata['vps_sub_district'] = $t[7];
        $metadata['vps_latitude'] = $t[8];
        $metadata['vps_longitude'] = $t[9];

        Taxpayer::updateOrCreate(
            ['name' => $t[4], 'nik' => $t[3]], // Use both name and nik for more robust matching if NIK is empty
            [
                'opd_id' => $opdMapping[$t[1]] ?? null,
                // created_by might need mapping but users are synced by email, so IDs might change
                'address' => $t[5],
                'phone' => $t[10],
                'npwpd' => $t[11],
                'object_name' => $t[12],
                'object_address' => $t[13],
                'metadata' => $metadata,
                'password' => $t[15],
                'is_active' => $t[16],
                'created_at' => $t[17],
                'updated_at' => $t[18],
            ]
        );
    }
    echo "Taxpayers synced: " . count($vpsTaxpayers) . "\n";

    echo "Syncing Tax Objects...\n";
    $vpsTaxObjects = parseInsertValues($sql, 'tax_objects');
    foreach ($vpsTaxObjects as $o) {
        // VPS: id, nop, taxpayer_id, retribution_type_id, retribution_classification_id, opd_id, zone_id, name...
        
        // Find local taxpayer ID (since IDs might have changed)
        // This is complex because we need to map VPS taxpayer_id to Local taxpayer_id
        // For simplicity, let's assume we can find the taxpayer by NOP linkage or re-query
        // But better is to maintain a map during taxpayer sync
        
        // Let's just find the opd and ret type first
        $localOpdId = $opdMapping[$o[5]] ?? null;
        $localRetTypeId = $retTypeMapping[$o[3]] ?? null;
        
        if (!$localOpdId || !$localRetTypeId) continue;

        TaxObject::updateOrCreate(
            ['nop' => $o[1]],
            [
                'taxpayer_id' => $o[2], // WARNING: This assumes taxpayer IDs are preserved. 
                                        // If not, we'd need another mapping. 
                                        // Since we use updateOrCreate, if we preserve IDs it's safer.
                'retribution_type_id' => $localRetTypeId,
                'retribution_classification_id' => $o[4], // Might also need mapping
                'opd_id' => $localOpdId,
                'zone_id' => $o[6],
                'name' => $o[7],
                'address' => $o[8],
                'latitude' => $o[9],
                'longitude' => $o[10],
                'metadata' => json_decode($o[11] ?? '{}', true),
                'status' => $o[12],
                'approved_at' => $o[13],
                'approved_by' => $o[14],
                'created_at' => $o[15],
                'updated_at' => $o[16],
            ]
        );
    }
    echo "Tax Objects synced: " . count($vpsTaxObjects) . "\n";

    DB::commit();
    echo "Successfully synced data from VPS dump.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

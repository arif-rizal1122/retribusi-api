<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpatialZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/spatial_zones_baubau.json'));
        $zones = json_decode($json, true);

        foreach ($zones as $zoneData) {
            // Get the first OPD associated with the retribution type to be safe
            $type = \App\Models\RetributionType::find($zoneData['retribution_type_id']);
            $opdId = $type ? $type->opd_id : null;

            if (!$opdId) continue;

            \App\Models\Zone::create([
                'opd_id' => $opdId,
                'retribution_type_id' => $zoneData['retribution_type_id'],
                'retribution_classification_id' => $zoneData['retribution_classification_id'],
                'name' => $zoneData['name'],
                'code' => $zoneData['code'],
                'geometry_type' => $zoneData['geometry_type'],
                'description' => $zoneData['description'],
                'coordinates' => $zoneData['coordinates'],
                // Set center point if it's a polygon for compatibility
                'latitude' => $zoneData['geometry_type'] === 'polygon' ? $zoneData['coordinates'][0][0] : null,
                'longitude' => $zoneData['geometry_type'] === 'polygon' ? $zoneData['coordinates'][0][1] : null,
            ]);
        }
    }
}

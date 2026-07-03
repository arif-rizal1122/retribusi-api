<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\RetributionClassification;

class AssignTaxClassificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure independent types have at least one classification
        $typesWithNoClasses = [
            20 => 'Standar Parkir',
            21 => 'PBB-P2 Perkotaan',
            22 => 'Standar Persampahan'
        ];

        foreach ($typesWithNoClasses as $typeId => $className) {
            $type = RetributionType::find($typeId);
            if ($type) {
                RetributionClassification::updateOrCreate(
                    ['name' => $className, 'retribution_type_id' => $typeId],
                    [
                        'opd_id' => $type->opd_id ?? 1,
                        'code' => strtoupper(str_replace(' ', '_', $className)),
                        'description' => "Klasifikasi standar untuk {$type->name}"
                    ]
                );
            }
        }

        // 2. Assign classifications to TaxObjects
        $objects = TaxObject::all();
        
        foreach ($objects as $obj) {
            // Find a classification linked to this type
            $classification = RetributionClassification::where('retribution_type_id', $obj->retribution_type_id)->first();
            
            // Special logic for "Wilayah I" (18) and "Wilayah II" (19)
            // They have multiple classifications (PBB, BPHTB, etc.)
            if ($obj->retribution_type_id == 18 || $obj->retribution_type_id == 19) {
                // If the name suggests a specific type, try to match it
                if (str_contains(strtolower($obj->name), 'reklame')) {
                    $classification = RetributionClassification::where('name', 'like', '%Reklame%')->first();
                } elseif (str_contains(strtolower($obj->name), 'rumah') || str_contains(strtolower($obj->name), 'pbb')) {
                    $classification = RetributionClassification::where('name', 'PBB-P2')->first();
                } else {
                    // Randomly assign one from the available ones for that type
                    $classification = RetributionClassification::where('retribution_type_id', $obj->retribution_type_id)
                        ->inRandomOrder()
                        ->first();
                }
            }

            if ($classification) {
                $obj->retribution_classification_id = $classification->id;
                $obj->save();
            }
        }
        
        $this->command->info('Successfully assigned classifications to ' . $objects->count() . ' tax objects.');
    }
}

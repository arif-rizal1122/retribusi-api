<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_items', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_items', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('metadata');
            }
        });

        // Seed realistic preview images for PUPR heavy equipment & sedot tinja armadas
        $defaultImages = [
            'SK-001' => 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=800&q=80', // Tanker / vacuum truck
            'SK-002' => 'https://images.unsplash.com/photo-1586191582056-a6f958f22495?auto=format&fit=crop&w=800&q=80', // Utility truck
            'SK-003' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=800&q=80', // Workshop maintenance
            'AB-01'  => 'https://images.unsplash.com/photo-1580901368919-7738efb0f87e?auto=format&fit=crop&w=800&q=80', // Excavator
            'AB-02'  => 'https://images.unsplash.com/photo-1541888946425-d0fbb180c5f7?auto=format&fit=crop&w=800&q=80', // Construction excavator
            'AB-03'  => 'https://images.unsplash.com/photo-1579487785973-74d2ca7abdd5?auto=format&fit=crop&w=800&q=80', // Wheel Loader
            'AB-04'  => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80', // Motor Grader
            'AB-05'  => 'https://images.unsplash.com/photo-1508873696983-2df5293cb39f?auto=format&fit=crop&w=800&q=80', // Road Roller / Compactor
            'AB-06'  => 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=800&q=80', // Dump Truck
            'AB-07'  => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=800&q=80', // Tronton Self Loader
        ];

        foreach ($defaultImages as $code => $url) {
            DB::table('asset_items')->where('code', $code)->update(['image_url' => $url]);
        }

        // Fallback for remaining items without image
        DB::table('asset_items')
            ->whereNull('image_url')
            ->where('category_id', 3) // Alat Berat
            ->update(['image_url' => 'https://images.unsplash.com/photo-1580901368919-7738efb0f87e?auto=format&fit=crop&w=800&q=80']);

        DB::table('asset_items')
            ->whereNull('image_url')
            ->where('category_id', 4) // Kendaraan
            ->update(['image_url' => 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=800&q=80']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_items', function (Blueprint $table) {
            if (Schema::hasColumn('asset_items', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};

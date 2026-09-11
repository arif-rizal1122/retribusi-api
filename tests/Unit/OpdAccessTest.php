<?php

namespace Tests\Unit;

use App\Models\Opd;
use App\Models\User;
use App\Support\OpdAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OpdAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('retribution_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opd_id');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sector_opd_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opd_id');
            $table->timestamps();
        });
    }

    public function test_bapenda_user_is_strictly_isolated_from_sectoral_opd_flags(): void
    {
        $bapendaOpd = new Opd([
            'id' => 5,
            'name' => 'Badan Pendapatan Daerah',
            'code' => 'BAPENDA',
        ]);

        $bapendaUser = new User([
            'name' => 'Admin BAPENDA',
            'email' => 'bapenda@baubaukota.go.id',
            'role' => 'opd',
            'opd_id' => 5,
        ]);
        $bapendaUser->setRelation('opd', $bapendaOpd);

        // Even if JU-REK classification has opd_id = 5 (legacy data anomaly)
        DB::table('retribution_classifications')->insert([
            'opd_id' => 5,
            'code' => 'JU-REK',
            'is_active' => true,
        ]);

        $access = OpdAccess::for($bapendaUser);

        $this->assertNotNull($access);
        $this->assertTrue($access['is_bapenda']);
        $this->assertTrue($access['flags']['pbb']);
        $this->assertTrue($access['flags']['bphtb']);

        // Sectoral OPD flags MUST be false for Bapenda
        $this->assertFalse($access['flags']['rekreasi'], 'Bapenda must not have rekreasi flag');
        $this->assertFalse($access['flags']['ptsp'], 'Bapenda must not have ptsp flag');
        $this->assertFalse($access['flags']['dishub'], 'Bapenda must not have dishub flag');
        $this->assertFalse($access['flags']['perikanan'], 'Bapenda must not have perikanan flag');
    }

    public function test_pariwisata_user_receives_rekreasi_flag(): void
    {
        $pariwisataOpd = new Opd([
            'id' => 16,
            'name' => 'Dinas Pariwisata dan Kebudayaan',
            'code' => 'PARIWISATA',
        ]);

        $pariwisataUser = new User([
            'name' => 'Admin Pariwisata',
            'email' => 'admin.pariwisata@baubaukota.go.id',
            'role' => 'opd',
            'opd_id' => 16,
        ]);
        $pariwisataUser->setRelation('opd', $pariwisataOpd);

        DB::table('retribution_classifications')->insert([
            'opd_id' => 16,
            'code' => 'JU-REK',
            'is_active' => true,
        ]);

        $access = OpdAccess::for($pariwisataUser);

        $this->assertNotNull($access);
        $this->assertFalse($access['is_bapenda']);
        $this->assertTrue($access['flags']['rekreasi'], 'Pariwisata OPD must receive rekreasi flag');
        $this->assertFalse($access['flags']['dishub']);
        $this->assertFalse($access['flags']['ptsp']);
    }

    public function test_ptsp_user_receives_ptsp_flag(): void
    {
        $ptspOpd = new Opd([
            'id' => 7,
            'name' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
            'code' => 'DPMPTSP',
        ]);

        $ptspUser = new User([
            'name' => 'Admin DPMPTSP',
            'email' => 'admin.dpmptsp@baubaukota.go.id',
            'role' => 'opd',
            'opd_id' => 7,
        ]);
        $ptspUser->setRelation('opd', $ptspOpd);

        $access = OpdAccess::for($ptspUser);

        $this->assertNotNull($access);
        $this->assertFalse($access['is_bapenda']);
        $this->assertTrue($access['flags']['ptsp'], 'PTSP OPD must receive ptsp flag');
        $this->assertFalse($access['flags']['rekreasi']);
        $this->assertFalse($access['flags']['dishub']);
    }
}

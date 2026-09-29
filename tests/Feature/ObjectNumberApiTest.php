<?php

namespace Tests\Feature;

use App\Services\LegacyDictionaryService;
use App\Services\ObjectNumberRegistryService;
use App\Services\ObjectNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Uji jalur DB kodifikasi NOPD/NORD: alokasi nomor, kamus legacy, klaim objek.
 * Memakai sqlite in-memory + hanya 3 migration M-PAD ini (tanpa migrate penuh).
 */
class ObjectNumberApiTest extends TestCase
{
    private array $migrations = [
        '2026_09_29_100001_create_ref_wilayah_table.php',
        '2026_09_29_100002_create_ref_legacy_dictionary_table.php',
        '2026_09_29_100003_create_object_number_registry_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->migrations as $file) {
            $migration = require database_path('migrations/'.$file);
            $migration->up();
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->migrations) as $file) {
            $migration = require database_path('migrations/'.$file);
            $migration->down();
        }

        parent::tearDown();
    }

    private function seedKamus(array $override = []): int
    {
        return DB::table('ref_legacy_dictionary')->insertGetId(array_merge([
            'legacy_source' => '9PAJAK_SW_PATDA',
            'legacy_code' => '8.01.720.015.46944',
            'legacy_npwpd' => 'P10000002087472',
            'owner_nik_or_nib' => '7471010101010001',
            'legal_entity_name' => 'PT Nur Syuhada',
            'commercial_brand_name' => 'Kedai Nur Syuhada',
            'sector_code' => '01',
            'kode_wilayah' => '7101',
            'address_text' => 'Jl. Dayanu Ikhsanuddin, Wolio',
            'technical_specs' => json_encode(['kursi' => 40, 'meja' => 10]),
            'claim_status' => 'UNCLAIMED',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $override));
    }

    public function test_matriks_sektor_endpoint_mengembalikan_21_sektor(): void
    {
        $res = $this->getJson('/api/object-numbers/sektor');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kode_kota', '7472');

        $this->assertCount(21, $res->json('data.sektor'));
    }

    public function test_terbitkan_nomor_menaikkan_urut_per_sektor_dan_wilayah(): void
    {
        $svc = app(ObjectNumberRegistryService::class);

        $a = $svc->terbitkan('01', '7101');
        $b = $svc->terbitkan('01', '7101');
        $c = $svc->terbitkan('22', '7101');

        $this->assertSame('74720171010001', $a->nomor);
        $this->assertSame('74720171010002', $b->nomor);
        $this->assertSame('74722271010001', $c->nomor);
        $this->assertSame('NOPD', $a->jenis);
        $this->assertSame('NORD', $c->jenis);
    }

    public function test_cek_nomor_endpoint_membedakan_terdaftar_dan_belum(): void
    {
        app(ObjectNumberRegistryService::class)->terbitkan('06', '7401');

        $this->getJson('/api/object-numbers/7472.06.7401.0001')
            ->assertOk()
            ->assertJsonPath('data.terdaftar', true)
            ->assertJsonPath('data.detail.sektor_nama', 'Pajak Reklame');

        $this->getJson('/api/object-numbers/7472.06.7401.0009')
            ->assertOk()
            ->assertJsonPath('data.terdaftar', false);
    }

    public function test_cek_nomor_tidak_valid_ditolak_422(): void
    {
        $this->getJson('/api/object-numbers/999')->assertStatus(422);
    }

    public function test_legacy_lookup_menemukan_objek_dari_nop_lama(): void
    {
        $this->seedKamus();

        $res = $this->postJson('/api/object-numbers/legacy-lookup', [
            'legacy_code' => '8.01.720.015.46944',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.kandidat.0.nama_objek_usaha', 'Kedai Nur Syuhada')
            ->assertJsonPath('data.kandidat.0.legal_entity_name', 'PT Nur Syuhada');
    }

    public function test_legacy_lookup_menemukan_objek_dari_npwpd_lama(): void
    {
        $this->seedKamus();

        $this->postJson('/api/object-numbers/legacy-lookup', ['npwpd' => 'P10000002087472'])
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_legacy_lookup_tanpa_kriteria_ditolak_422(): void
    {
        $this->postJson('/api/object-numbers/legacy-lookup', [])->assertStatus(422);
    }

    public function test_kandidat_klaim_muncul_saat_nik_cocok(): void
    {
        $this->seedKamus();

        $this->getJson('/api/object-numbers/kandidat-klaim?nik=7471010101010001')
            ->assertOk()
            ->assertJsonPath('data.ada_tawaran', true);

        $this->getJson('/api/object-numbers/kandidat-klaim?nik=7471010101019999')
            ->assertOk()
            ->assertJsonPath('data.ada_tawaran', false);
    }

    public function test_klaim_menerbitkan_nomor_baru_dan_menyimpan_alias_lama(): void
    {
        $id = $this->seedKamus();
        $legacy = app(LegacyDictionaryService::class);
        $registry = app(ObjectNumberRegistryService::class);

        $hasil = $legacy->klaim($id, [
            'kode_wilayah' => '7101',
            'latitude' => -5.4573,
            'longitude' => 122.6035,
        ], $registry);

        $this->assertSame('74720171010001', $hasil['nomor']);
        $this->assertSame('7472.01.7101.0001', $hasil['tampilan']);
        $this->assertSame('CLAIMED', $hasil['claim_status']);

        $dict = DB::table('ref_legacy_dictionary')->where('id', $id)->first();
        $this->assertSame('CLAIMED', $dict->claim_status);
        $this->assertNotNull($dict->claimed_at);

        $row = DB::table('object_number_registry')->where('nomor', $hasil['nomor'])->first();
        $this->assertSame('8.01.720.015.46944', $row->legacy_code);
        $this->assertSame('P10000002087472', $row->legacy_npwpd);
    }

    public function test_objek_yang_sudah_verified_tidak_bisa_diklaim_ulang(): void
    {
        $id = $this->seedKamus(['claim_status' => 'VERIFIED']);
        $legacy = app(LegacyDictionaryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $legacy->klaim($id, ['kode_wilayah' => '7101'], app(ObjectNumberRegistryService::class));
    }

    public function test_pencarian_loket_menemukan_nomor_lewat_kode_lama(): void
    {
        $id = $this->seedKamus();
        $legacy = app(LegacyDictionaryService::class);
        $registry = app(ObjectNumberRegistryService::class);
        $legacy->klaim($id, ['kode_wilayah' => '7101'], $registry);

        $hasil = $registry->cariApaSaja('8.01.720.015.46944');
        $this->assertCount(1, $hasil);
        $this->assertSame('74720171010001', $hasil[0]->nomor);

        $viaNomor = $registry->cariApaSaja('7472.01.7101.0001');
        $this->assertCount(1, $viaNomor);
    }

    public function test_retire_nomor_tidak_menghapus_data(): void
    {
        $registry = app(ObjectNumberRegistryService::class);
        $record = $registry->terbitkan('01', '7101');

        $this->assertTrue($registry->retire($record->nomor, 'renumbering'));
        $this->assertSame('retired', $record->fresh()->status);
        $this->assertSame('renumbering', $record->fresh()->metadata['retired_reason']);
        $this->assertDatabaseHas('object_number_registry', ['nomor' => $record->nomor]);
    }

    public function test_kolom_kamus_memuat_spesifikasi_teknis_dari_data_lama(): void
    {
        $id = $this->seedKamus();
        $dict = app(LegacyDictionaryService::class)->cari(['nik' => '7471010101010001'])->first();

        $this->assertSame(40, $dict->spesifikasi('kursi'));
        $this->assertSame('Kedai Nur Syuhada', $dict->namaTampil());
        $this->assertSame($id, $dict->id);
    }

    public function test_schema_tiga_tabel_terbentuk(): void
    {
        foreach (['ref_wilayah', 'ref_legacy_dictionary', 'object_number_registry'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak terbentuk");
        }

        $this->assertTrue(Schema::hasColumn('object_number_registry', 'legacy_code'));
        $this->assertTrue(Schema::hasColumn('ref_legacy_dictionary', 'technical_specs'));
    }

    public function test_seeder_wilayah_memuat_8_kecamatan_dan_43_kelurahan_resmi(): void
    {
        (new \Database\Seeders\RefWilayahBaubauSeeder)->run();

        $this->assertSame(43, DB::table('ref_wilayah')->count());
        $this->assertSame(8, DB::table('ref_wilayah')->distinct()->count('kode_kecamatan'));

        // Pemetaan nyata dari SW_PATDA (bukan tebakan)
        $this->assertDatabaseHas('ref_wilayah', [
            'kode' => '7103',
            'kecamatan' => 'WOLIO',
            'kelurahan' => 'WALE',
            'legacy_kode' => '7472710003',
        ]);
        $this->assertDatabaseHas('ref_wilayah', [
            'kode' => '7702',
            'kecamatan' => 'BATU POARO',
            'kelurahan' => 'WAMEO',
        ]);
        $this->assertDatabaseHas('ref_wilayah', [
            'kode' => '7215',
            'kelurahan' => 'LIPUU',
            'kode_kelurahan' => '15',
        ]);
        $this->assertDatabaseHas('ref_wilayah', [
            'kode' => '7807',
            'kecamatan' => 'LEA-LEA',
            'kelurahan' => 'PALABUSA',
        ]);

        $this->assertSame(43, DB::table('ref_wilayah')->where('is_verified', true)->count());
    }

    public function test_seeder_wilayah_idempoten_tidak_menggandakan_baris(): void
    {
        (new \Database\Seeders\RefWilayahBaubauSeeder)->run();
        (new \Database\Seeders\RefWilayahBaubauSeeder)->run();

        $this->assertSame(43, DB::table('ref_wilayah')->count());
    }

    public function test_kode_wilayah_matriks_selaras_dengan_kode_sw_patda(): void
    {
        // Kecamatan SW_PATDA 7472 + 7X + 0 padan dengan kode M-PAD 7X
        foreach (['71' => '7472710', '78' => '7472780'] as $kodeMpad => $legacyKec) {
            $this->assertSame('7472'.$kodeMpad.'0', $legacyKec);
        }
    }

    public function test_pasang_nomor_lama_apa_adanya(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $record = $registry->pasang('8.01.720.007.10000', [
            'sector_code' => '01',
            'kode_wilayah' => '7103',
            'tax_object_id' => 12345,
        ]);

        $this->assertSame('8.01.720.007.10000', $record->nomor);
        $this->assertSame('existing', $record->nomor_sumber);
        $this->assertNull($record->nomor_baku);
        $this->assertSame('01', $record->sector_code);
    }

    public function test_pasang_nomor_13_digit_menyimpan_nomor_baku_bila_14_digit(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $lama = $registry->pasang('7017100030001');
        $this->assertSame('7017100030001', $lama->nomor);
        $this->assertSame('existing', $lama->nomor_sumber);
        $this->assertNull($lama->nomor_baku);

        // Nomor 14 digit bertitik: bentuk asli dipasang apa adanya, versi baku disimpan terpisah
        $baku = $registry->pasang('7472.01.7103.0007');
        $this->assertSame('7472.01.7103.0007', $baku->nomor);
        $this->assertSame('74720171030007', $baku->nomor_baku);

        // Pencarian pakai bentuk baku tetap menemukan nomor yang tersimpan bertitik
        $this->assertSame($baku->id, $registry->cariNomor('74720171030007')->id);
        $this->assertSame($baku->id, $registry->cariNomor('7472.01.7103.0007')->id);
    }

    public function test_pasang_idempoten_tidak_mengubah_nomor_yang_sudah_ada(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $pertama = $registry->pasang('7017100030001', ['sector_code' => '01']);
        $kedua = $registry->pasang('7017100030001', ['sector_code' => '02']);

        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame('01', $kedua->fresh()->sector_code, 'Nomor yang sudah ada tidak boleh diubah');
        $this->assertSame(1, DB::table('object_number_registry')->count());
    }

    public function test_pasang_menolak_hash_placeholder(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $registry->pasang('00014bebb952fca411cf74b64e5fef07');
    }

    public function test_pastikan_nomor_memasang_yang_sudah_ada(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $record = $registry->pastikanNomor('8.01.710.003.0189', '01', '7103');

        $this->assertSame('8.01.710.003.0189', $record->nomor);
        $this->assertSame('existing', $record->nomor_sumber);
    }

    public function test_pastikan_nomor_generate_untuk_hash_dan_kosong(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $dariHash = $registry->pastikanNomor('00014bebb952fca411cf74b64e5fef07', '01', '7103');
        $this->assertSame('generated', $dariHash->nomor_sumber);
        $this->assertSame('74720171030001', $dariHash->nomor);

        $dariKosong = $registry->pastikanNomor(null, '01', '7103');
        $this->assertSame('generated', $dariKosong->nomor_sumber);
        $this->assertSame('74720171030002', $dariKosong->nomor);
    }

    public function test_pastikan_nomor_butuh_kode_wilayah_untuk_generate(): void
    {
        $registry = app(ObjectNumberRegistryService::class);

        $this->expectException(\InvalidArgumentException::class);
        $registry->pastikanNomor(null, '01', null);
    }

    public function test_cari_nomor_bisa_menemukan_nomor_lama_bertitik(): void
    {
        $registry = app(ObjectNumberRegistryService::class);
        $registry->pasang('8.01.720.007.10000');

        $this->assertNotNull($registry->cariNomor('8.01.720.007.10000'));
        $this->assertNotNull($registry->cariNomor('  8.01.720.007.10000 '));
        $this->assertNull($registry->cariNomor(''));
        $this->assertNull($registry->cariNomor('9999999999'));
    }

    public function test_endpoint_sektor_memakai_kode_wilayah_dari_matriks(): void
    {
        $service = app(ObjectNumberService::class);

        foreach (array_keys($service->sektorMap()) as $sektor) {
            $this->assertTrue($service->isValid($service->generate($sektor, '7101', 7)));
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\AssetToken;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\SignedDocument;
use App\Models\Taxpayer;
use App\Models\TteLog;
use App\Services\AutoTteHookService;
use App\Services\TTEService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TteBsreIntegrationAndEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opdPupr;
    private Opd $opdBapenda;
    private RetributionType $retributionAsset;
    private RetributionType $retributionTax;
    private Taxpayer $taxpayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opdPupr = Opd::firstOrCreate(['code' => 'PUPR'], [
            'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
        ]);

        $this->opdBapenda = Opd::firstOrCreate(['code' => 'BAPENDA'], [
            'name' => 'Badan Pendapatan Daerah',
        ]);

        $this->retributionAsset = RetributionType::firstOrCreate(
            ['name' => 'Sewa Alat Berat & Utilitas PUPR'],
            [
                'opd_id' => $this->opdPupr->id,
                'category' => 'Aset',
                'tariff' => 900000,
                'satuan' => 'hari',
            ]
        );

        $this->retributionTax = RetributionType::firstOrCreate(
            ['name' => 'Pajak Daerah PBJT'],
            [
                'opd_id' => $this->opdBapenda->id,
                'category' => 'Pajak',
                'tariff' => 100000,
                'satuan' => 'omzet',
            ]
        );

        $this->taxpayer = Taxpayer::firstOrCreate(
            ['nik' => '7471019901010001'],
            [
                'name' => 'PT BUTON KONSTRUKSI NUSANTARA',
                'email' => 'pt.bkn@baubaukota.test',
                'address' => 'Jl. Palagimata No. 12, Kota Baubau',
            ]
        );
    }

    /**
     * Uji Token Registrasi Aset dan Penandatanganan Transisi OPD
     */
    public function test_asset_transitional_token_generation_and_signing(): void
    {
        $tteService = app(TTEService::class);

        // 1. Generate Token Transisi
        $token = $tteService->generateAssetTransitionToken(
            opdCode: 'PUPR',
            tenant: 'PT BUTON KONSTRUKSI NUSANTARA',
            validDays: 14
        );

        $this->assertInstanceOf(AssetToken::class, $token);
        $this->assertStringStartsWith('PUPR-AST-', $token->token_code);
        $this->assertTrue($token->is_active);
        $this->assertDatabaseHas('asset_tokens', [
            'token_code' => $token->token_code,
            'opd_code' => 'PUPR',
        ]);

        // 2. Buat Tagihan Aset
        $bill = Bill::create([
            'bill_number' => 'BIL-TEST-AST-' . time(),
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionAsset->id,
            'opd_id' => $this->opdPupr->id,
            'amount' => 2700000,
            'status' => 'lunas',
            'period' => date('Y-m'),
            'due_date' => now()->addDays(30),
        ]);

        // 3. Tanda tangani dengan Token Transisi
        $signedDoc = $tteService->signAssetWithTransitionToken(
            documentable: $bill,
            opdCode: 'PUPR',
            tenantIdentity: $this->taxpayer->name,
            notes: 'Uji integrasi perikatan sewa alat berat'
        );

        $this->assertInstanceOf(SignedDocument::class, $signedDoc);
        $this->assertEquals('NUMERIC_TOKEN_TRANSITION', $signedDoc->signature_type);
        $this->assertEquals('signed', $signedDoc->status);
        $this->assertStringContainsString('PUPR', $signedDoc->signer_name);
        $this->assertNotNull($signedDoc->signature_hash);
        $this->assertNotNull($signedDoc->sha256_checksum);
        $this->assertDatabaseHas('signed_documents', [
            'document_number' => $bill->bill_number,
            'signature_type' => 'NUMERIC_TOKEN_TRANSITION',
        ]);
    }

    /**
     * Uji TTEService Fallback Kriptografis dan Audit Trail tte_logs
     */
    public function test_tte_service_audit_trail_and_cryptographic_seal(): void
    {
        $tteService = app(TTEService::class);

        $bill = Bill::create([
            'bill_number' => 'BIL-TAX-' . time(),
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionTax->id,
            'opd_id' => $this->opdBapenda->id,
            'amount' => 500000,
            'status' => 'lunas',
            'period' => date('Y-m'),
            'due_date' => now()->addDays(30),
        ]);

        $initialLogCount = TteLog::count();

        $signedDoc = $tteService->signDocument(
            documentable: $bill,
            signer: null,
            signatureType: 'ELECTRONIC_SEAL'
        );

        $this->assertInstanceOf(SignedDocument::class, $signedDoc);
        $this->assertEquals('signed', $signedDoc->status);
        $this->assertEquals('ELECTRONIC_SEAL', $signedDoc->signature_type);

        // Pastikan tte_logs mencatat aktivitas
        $this->assertGreaterThan($initialLogCount, TteLog::count());
        $latestLog = TteLog::where('document_number', $bill->bill_number)->first();
        $this->assertNotNull($latestLog);
        $this->assertContains($latestLog->status, ['success', 'fallback_local_seal']);
    }

    /**
     * Uji AutoTteHookService otomatis membedakan Pajak, Retribusi, dan Aset
     */
    public function test_auto_tte_hook_service_dispatch_and_idempotency(): void
    {
        $hookService = app(AutoTteHookService::class);

        // 1. Tagihan Pajak (SSPD)
        $taxBill = Bill::create([
            'bill_number' => 'BIL-HOOK-TAX-' . time(),
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionTax->id,
            'opd_id' => $this->opdBapenda->id,
            'amount' => 1250000,
            'status' => 'lunas',
            'period' => date('Y-m'),
            'due_date' => now()->addDays(30),
        ]);

        $taxDoc = $hookService->handlePaidBill($taxBill, 'QRIS', 'NTPD-TAX-001');
        $this->assertNotNull($taxDoc);
        $this->assertStringContainsString('SSPD', $taxDoc->document_number);

        // 2. Uji Idempotency: eksekusi kedua kali tidak menduplikasi record
        $taxDocSecond = $hookService->handlePaidBill($taxBill, 'QRIS', 'NTPD-TAX-001');
        $this->assertEquals($taxDoc->id, $taxDocSecond->id);

        // 3. Tagihan Aset (SIPAB/SIPA)
        $assetBill = Bill::create([
            'bill_number' => 'BIL-HOOK-AST-' . time(),
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionAsset->id,
            'opd_id' => $this->opdPupr->id,
            'amount' => 2700000,
            'status' => 'lunas',
            'period' => date('Y-m'),
            'due_date' => now()->addDays(30),
        ]);

        $assetDoc = $hookService->handlePaidBill($assetBill, 'SNAP_BI', 'NTPD-AST-002');
        $this->assertNotNull($assetDoc);
        $this->assertEquals('NUMERIC_TOKEN_TRANSITION', $assetDoc->signature_type);
    }

    /**
     * Uji Pembuatan & Streaming Dokumen PDF (SSPD, SSRD, SIPAB)
     */
    public function test_document_controller_can_render_sspd_ssrd_and_sipab_pdf(): void
    {
        $bill = Bill::create([
            'bill_number' => 'BIL-PDF-' . time(),
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionAsset->id,
            'opd_id' => $this->opdPupr->id,
            'amount' => 1500000,
            'status' => 'lunas',
            'period' => date('Y-m'),
            'due_date' => now()->addDays(30),
        ]);

        // 1. Endpoint SSPD
        $resSspd = $this->get("/api/public/pdf/sspd/{$bill->id}");
        $resSspd->assertStatus(200);
        $resSspd->assertHeader('content-type', 'application/pdf');

        // 2. Endpoint SSRD
        $resSsrd = $this->get("/api/public/pdf/ssrd/{$bill->id}");
        $resSsrd->assertStatus(200);
        $resSsrd->assertHeader('content-type', 'application/pdf');

        // 3. Endpoint SIPAB
        $resSipab = $this->get("/api/public/pdf/sipab/{$bill->id}");
        $resSipab->assertStatus(200);
        $resSipab->assertHeader('content-type', 'application/pdf');
    }
}

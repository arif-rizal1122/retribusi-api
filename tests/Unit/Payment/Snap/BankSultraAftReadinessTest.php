<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\BankSultraAftReadiness;
use Tests\TestCase;

class BankSultraAftReadinessTest extends TestCase
{
    public function test_it_is_unavailable_by_default(): void
    {
        $this->assertSame(
            'BANK_SULTRA_AFT_ENABLED belum diaktifkan.',
            app(BankSultraAftReadiness::class)->unavailableReason()
        );
    }

    public function test_it_accepts_complete_direct_debit_h2h_config(): void
    {
        config([
            'snap.bank_sultra_aft.enabled' => true,
            'snap.bank_sultra_aft.selected_product' => 'direct_debit_h2h',
            'snap.bank_sultra_aft.base_url' => 'https://sandbox.bank-sultra.test',
            'snap.partners.BANK_SULTRA.partner_id' => 'SULTRA-PARTNER',
            'snap.partners.BANK_SULTRA.client_key' => 'SULTRA-CLIENT',
            'snap.partners.BANK_SULTRA.client_secret' => 'sultra-client-secret',
            'snap.partners.BANK_SULTRA.channel_id' => '95221',
            'snap.partners.BANK_SULTRA.signature_algorithm' => 'hmac_sha512',
            'snap.partners.BANK_SULTRA.signature_secret' => 'sultra-signature-secret',
            'snap.bank_sultra_aft.timeout_seconds' => 15,
            'snap.bank_sultra_aft.allowed_ips' => ['127.0.0.1'],
            'snap.bank_sultra_aft.source_account_no' => '116010001234567',
            'snap.bank_sultra_aft.destination_account_no' => '116010009876543',
        ]);

        $this->assertNull(app(BankSultraAftReadiness::class)->unavailableReason());
    }

    public function test_it_rejects_placeholder_base_url_when_enabled(): void
    {
        config([
            'snap.bank_sultra_aft.enabled' => true,
            'snap.bank_sultra_aft.selected_product' => 'direct_debit_h2h',
            'snap.bank_sultra_aft.base_url' => 'https://api.banksultra.co.id/snap/v1.0/direct-debit/payment',
            'snap.partners.BANK_SULTRA.partner_id' => 'SULTRA-PARTNER',
            'snap.partners.BANK_SULTRA.client_key' => 'SULTRA-CLIENT',
            'snap.partners.BANK_SULTRA.client_secret' => 'sultra-client-secret',
            'snap.partners.BANK_SULTRA.channel_id' => '95221',
            'snap.partners.BANK_SULTRA.signature_algorithm' => 'hmac_sha512',
            'snap.partners.BANK_SULTRA.signature_secret' => 'sultra-signature-secret',
            'snap.bank_sultra_aft.timeout_seconds' => 15,
            'snap.bank_sultra_aft.allowed_ips' => ['127.0.0.1'],
            'snap.bank_sultra_aft.source_account_no' => '1234567890',
            'snap.bank_sultra_aft.destination_account_no' => '0987654321',
        ]);

        $this->assertSame(
            'BANK_SULTRA_SNAP_BASE_URL masih memakai placeholder lama.',
            app(BankSultraAftReadiness::class)->unavailableReason()
        );
    }
}

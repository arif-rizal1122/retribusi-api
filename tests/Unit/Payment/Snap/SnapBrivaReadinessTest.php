<?php

namespace Tests\Unit\Payment\Snap;

use App\Services\Payment\Snap\SnapBrivaReadiness;
use Tests\TestCase;

class SnapBrivaReadinessTest extends TestCase
{
    public function test_it_accepts_local_rsa_public_key_and_briva_prefix_config(): void
    {
        config([
            'snap.briva.enabled' => true,
            'snap.partners.BRI.partner_id' => 'BRI-PARTNER-LOCAL',
            'snap.partners.BRI.client_key' => 'BRI-CLIENT-LOCAL',
            'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
            'snap.partners.BRI.signature_secret' => '',
            'snap.partners.BRI.public_key' => $this->publicKey(),
            'snap.partners.BRI.public_key_path' => '',
            'snap.briva.partner_service_id' => '',
            'snap.briva.va_prefix' => '19960',
            'snap.briva.customer_no_length' => 20,
            'snap.briva.va_length' => 18,
        ]);

        $this->assertNull(app(SnapBrivaReadiness::class)->unavailableReason());
    }

    public function test_it_accepts_bri_confirmed_prefix_without_partner_service_id_for_sandbox(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            config([
                'snap.briva.enabled' => true,
                'snap.partners.BRI.partner_id' => 'BRI-PARTNER-SANDBOX',
                'snap.partners.BRI.client_key' => 'BRI-CLIENT-SANDBOX',
                'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
                'snap.partners.BRI.signature_secret' => '',
                'snap.partners.BRI.public_key' => $this->publicKey(),
                'snap.partners.BRI.public_key_path' => '',
                'snap.briva.partner_service_id' => '',
                'snap.briva.va_prefix' => '19960',
                'snap.briva.customer_no_length' => 20,
                'snap.briva.va_length' => 18,
                'snap.allowed_ips' => ['127.0.0.1'],
            ]);

            $this->assertNull(app(SnapBrivaReadiness::class)->unavailableReason());
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_it_rejects_dummy_prefix_outside_local_smoke(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            config([
                'snap.briva.enabled' => true,
                'snap.partners.BRI.partner_id' => 'BRI-PARTNER-SANDBOX',
                'snap.partners.BRI.client_key' => 'BRI-CLIENT-SANDBOX',
                'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
                'snap.partners.BRI.signature_secret' => '',
                'snap.partners.BRI.public_key' => $this->publicKey(),
                'snap.partners.BRI.public_key_path' => '',
                'snap.briva.partner_service_id' => '',
                'snap.briva.va_prefix' => '777',
                'snap.briva.customer_no_length' => 20,
                'snap.briva.va_length' => 18,
                'snap.allowed_ips' => ['127.0.0.1'],
            ]);

            $this->assertSame(
                'Prefix VA dummy hanya boleh dipakai untuk local smoke.',
                app(SnapBrivaReadiness::class)->unavailableReason()
            );
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_it_rejects_public_key_material_in_hmac_secret(): void
    {
        config([
            'snap.briva.enabled' => true,
            'snap.partners.BRI.partner_id' => 'BRI-PARTNER-LOCAL',
            'snap.partners.BRI.client_key' => 'BRI-CLIENT-LOCAL',
            'snap.partners.BRI.signature_algorithm' => 'hmac_sha512',
            'snap.partners.BRI.signature_secret' => $this->publicKey(),
            'snap.briva.va_prefix' => '19960',
            'snap.briva.va_length' => 18,
        ]);

        $this->assertSame(
            'BRI_SNAP_SIGNATURE_SECRET terlihat seperti public key. Gunakan BRI_SNAP_PUBLIC_KEY_PATH untuk RSA.',
            app(SnapBrivaReadiness::class)->unavailableReason()
        );
    }

    public function test_it_rejects_invalid_rsa_public_key(): void
    {
        config([
            'snap.briva.enabled' => true,
            'snap.partners.BRI.partner_id' => 'BRI-PARTNER-LOCAL',
            'snap.partners.BRI.client_key' => 'BRI-CLIENT-LOCAL',
            'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
            'snap.partners.BRI.public_key' => 'not-a-public-key',
            'snap.briva.va_prefix' => '19960',
            'snap.briva.va_length' => 18,
        ]);

        $this->assertSame('Public key BRI tidak valid.', app(SnapBrivaReadiness::class)->unavailableReason());
    }

    private function publicKey(): string
    {
        return <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwYlN41PX9hRBKFRzmtsl
V+E2ON+xxbGUrA0wOwFgmrQNFBuQFqQ2Oe5w15Zu0E5mnNl8EHby3cLU1Kue0JiO
K970LMuSyc57x1I+FWOxuBNoi1YxdPjZBdznxk/DzdIPqIVBFHIHJho+ZNCPG+Ch
spWMD5lo4T+sHhnXxzgLKkBeQcVI2fmCUCp6iTaIwgNWl6L7mG9qo60z/1ae6b+1
GdW6EnuwEgRaXPZDW/zwxV0xAU8XoIB/1DNu4qE5QJrB4u5NVTNvAHjtmo8R7cwp
v4B8OlqYl0c3Ki6pGLMZUqZ/7ZLz3aV+3jsihMVACFiPqtBGUCk2Ie4PuuRxw8/7
5QIDAQAB
-----END PUBLIC KEY-----
PEM;
    }
}

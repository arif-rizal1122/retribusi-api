<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\TaxDomainHelper;
use Tests\TestCase;

class TaxDomainHelperTest extends TestCase
{
    public function test_get_domain_for_wilayah_1(): void
    {
        $user = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_1'],
        ]);

        $this->assertEquals(TaxDomainHelper::DOMAIN_WILAYAH_1, TaxDomainHelper::getDomain($user));
    }

    public function test_get_domain_for_wilayah_2(): void
    {
        $user = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_2'],
        ]);

        $this->assertEquals(TaxDomainHelper::DOMAIN_WILAYAH_2, TaxDomainHelper::getDomain($user));
    }

    public function test_super_admin_gets_all_domain(): void
    {
        $user = new User([
            'role' => 'super_admin',
            'metadata' => ['tax_domain' => 'wilayah_1'],
        ]);

        $this->assertEquals(TaxDomainHelper::DOMAIN_ALL, TaxDomainHelper::getDomain($user));
    }

    public function test_wilayah_1_can_approve_pbb_mutation(): void
    {
        $user = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_1'],
        ]);

        $this->assertTrue(TaxDomainHelper::canApprove($user, 'pbb_mutation'));
        $this->assertTrue(TaxDomainHelper::canApprove($user, 'pbb_reversal'));
        $this->assertTrue(TaxDomainHelper::canApprove($user, 'notaris_verification'));
        $this->assertFalse(TaxDomainHelper::canApprove($user, 'pbjt_self_assessment'));
    }

    public function test_wilayah_2_cannot_approve_pbb_mutation(): void
    {
        $user = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_2'],
        ]);

        $this->assertFalse(TaxDomainHelper::canApprove($user, 'pbb_mutation'));
        $this->assertFalse(TaxDomainHelper::canApprove($user, 'pbb_reversal'));
        $this->assertFalse(TaxDomainHelper::canApprove($user, 'notaris_verification'));
        $this->assertTrue(TaxDomainHelper::canApprove($user, 'pbjt_self_assessment'));
    }

    public function test_penalty_waiver_domain_check(): void
    {
        $wilayah1User = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_1'],
        ]);

        $wilayah2User = new User([
            'role' => 'kepala_bapenda',
            'metadata' => ['tax_domain' => 'wilayah_2'],
        ]);

        // 186 = PBB-P2 (Wilayah 1)
        $this->assertTrue(TaxDomainHelper::canApprove($wilayah1User, 'penalty_waiver', 186));
        $this->assertFalse(TaxDomainHelper::canApprove($wilayah2User, 'penalty_waiver', 186));

        // 192 = PBJT-FOOD (Wilayah 2)
        $this->assertFalse(TaxDomainHelper::canApprove($wilayah1User, 'penalty_waiver', 192));
        $this->assertTrue(TaxDomainHelper::canApprove($wilayah2User, 'penalty_waiver', 192));
    }
}

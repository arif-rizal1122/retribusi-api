<?php

namespace Tests\Unit;

use App\Support\CitizenBillStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CitizenBillStatusTest extends TestCase
{
    public static function cases(): array
    {
        return require __DIR__.'/../Fixtures/citizen_bill_status_cases.php';
    }

    #[DataProvider('cases')]
    public function test_citizen_status_and_permissions(
        ?string $raw, bool $claim, bool $request, bool $pastDue,
        string $expectedStatus, bool $expectedPayable, string $expectedLabel,
    ): void {
        $status = CitizenBillStatus::normalize($raw, $claim, $request, $pastDue);
        $this->assertSame($expectedStatus, $status);
        $this->assertSame($expectedPayable, CitizenBillStatus::canPay($status));
        $this->assertSame($expectedLabel, CitizenBillStatus::label($status, $request));
    }
}

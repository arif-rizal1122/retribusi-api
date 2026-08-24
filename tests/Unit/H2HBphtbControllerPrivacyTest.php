<?php

namespace Tests\Unit;

use App\Http\Controllers\H2HBphtbController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class H2HBphtbControllerPrivacyTest extends TestCase
{
    public function test_public_sspd_nik_masking_pattern(): void
    {
        $reflection = new ReflectionClass(H2HBphtbController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('maskNik');
        $method->setAccessible(true);

        $this->assertSame('747201******0002', $method->invoke($controller, '7472011234560002'));
        $this->assertSame('747201******0002', $method->invoke($controller, '747201-123456-0002'));
        $this->assertNull($method->invoke($controller, null));
    }
}

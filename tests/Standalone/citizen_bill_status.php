<?php

// Runs the real pure status mapper without Laravel, a database, or Composer packages.
require_once __DIR__.'/../../app/Support/CitizenBillStatus.php';

use App\Support\CitizenBillStatus;

$cases = require __DIR__.'/../Fixtures/citizen_bill_status_cases.php';
foreach ($cases as $name => [$raw, $claim, $request, $pastDue, $expectedStatus, $expectedPayable, $expectedLabel]) {
    $status = CitizenBillStatus::normalize($raw, $claim, $request, $pastDue);
    $actual = [$status, CitizenBillStatus::canPay($status), CitizenBillStatus::label($status, $request)];
    if ($actual !== [$expectedStatus, $expectedPayable, $expectedLabel]) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}
fwrite(STDOUT, count($cases)." cases passed (status, can_pay, label); no Laravel/HTTP/database test performed.\n");

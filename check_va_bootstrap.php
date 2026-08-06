<?php
// Bootstrap Laravel langsung + query VA 334/335
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$vas = App\Models\PaymentRequest::whereIn('id', [334, 335])
    ->with(['items.bill'])
    ->orderBy('id')
    ->get();

foreach ($vas as $va) {
    echo "=== PaymentRequest #{$va->id} ===\n";
    echo "external_id   : {$va->external_id}\n";
    echo "va_number     : [{$va->va_number}] (len=" . strlen($va->va_number) . ")\n";
    echo "channel/method: {$va->payment_channel} / {$va->method}\n";
    echo "status        : {$va->status}\n";
    echo "amount_snap   : " . number_format($va->amount_snapshot, 2) . "\n";
    echo "admin_fee_snap: " . number_format($va->admin_fee_snapshot, 2) . "\n";
    echo "penalty_snap  : " . number_format($va->penalty_snapshot, 2) . "\n";
    echo "total_amount  : " . number_format($va->amount_snapshot + $va->admin_fee_snapshot + $va->penalty_snapshot, 2) . "\n";
    echo "expires_at    : {$va->expires_at}\n";
    echo "created_at    : {$va->created_at}\n";
    echo "paid_at       : " . ($va->paid_at ?? 'NULL') . "\n";
    echo "--- items ---\n";
    foreach ($va->items as $item) {
        $b = $item->bill;
        echo "  item#{$item->id}: bill_id={$item->bill_id} status_item={$item->status} amount={$item->amount_snapshot}\n";
        if ($b) {
            echo "    bill status={$b->status} amount=" . number_format($b->amount, 2) . " total=" . number_format($b->total_amount, 2) . " no_billing={$b->billing_number}\n";
        }
    }
    echo "\n";
}

<?php
$user = App\Models\User::where('email', 'petugas@bapenda.go.id')->first();
if (!$user) { echo "User not found\n"; exit; }

$taxObjects = App\Models\TaxObject::whereHas('taxpayer', function($q) use ($user) {
    $q->where('created_by', $user->id);
})->get();

if ($taxObjects->isEmpty()) { echo "No tax objects found for this petugas\n"; exit; }

$count = 0;
foreach($taxObjects as $obj) {
    // Generate an unpaid/active bill
    App\Models\Bill::create([
        'tax_object_id' => $obj->id,
        'taxpayer_id' => $obj->taxpayer_id,
        'retribution_type_id' => $obj->retribution_type_id,
        'bill_number' => 'INV-' . strtoupper(uniqid()),
        'period' => date('Y-m'),
        'due_date' => \Carbon\Carbon::now()->addDays(30),
        'amount' => rand(50, 200) * 1000,
        'status' => 'active',
        'is_paid' => false,
    ]);
    
    // Generate a paid bill
    $billPaid = App\Models\Bill::create([
        'tax_object_id' => $obj->id,
        'taxpayer_id' => $obj->taxpayer_id,
        'retribution_type_id' => $obj->retribution_type_id,
        'bill_number' => 'INV-' . strtoupper(uniqid()),
        'period' => date('Y-m', strtotime('-1 month')),
        'due_date' => \Carbon\Carbon::now()->subDays(5),
        'amount' => rand(50, 200) * 1000,
        'status' => 'lunas',
        'is_paid' => true,
    ]);
    
    // Generate a payment for the paid bill
    App\Models\Payment::create([
        'bill_id' => $billPaid->id,
        'tax_object_id' => $obj->id,
        'taxpayer_id' => $obj->taxpayer_id,
        'amount' => $billPaid->amount,
        'payment_method' => 'cash',
        'status' => 'success',
        'transaction_id' => 'PAY-' . strtoupper(uniqid()),
        'billing_period' => $billPaid->period,
        'paid_at' => \Carbon\Carbon::now()->subDays(10),
        'approved_by' => $user->id,
    ]);
    
    // Generate an overdue bill
    App\Models\Bill::create([
        'tax_object_id' => $obj->id,
        'taxpayer_id' => $obj->taxpayer_id,
        'retribution_type_id' => $obj->retribution_type_id,
        'bill_number' => 'INV-' . strtoupper(uniqid()),
        'period' => date('Y-m', strtotime('-2 months')),
        'due_date' => \Carbon\Carbon::now()->subDays(20),
        'amount' => rand(50, 200) * 1000,
        'status' => 'overdue',
        'is_paid' => false,
    ]);
    $count += 3;
}
echo "Created $count bills and dummy payments successfully!\n";

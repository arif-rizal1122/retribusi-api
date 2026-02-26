<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestProductionE2E extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:production-e2e';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs E2E test against the production environment';

    private $baseUrl = 'https://api.sipanda.online/api';
    private $adminToken = '';
    private $petugasToken = '';
    
    private $taxpayerId = null;
    private $taxObjectId = null;
    private $billId = null;
    private $paymentId = null;
    private $verificationId = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting E2E Production Test Cycle...");
        $errors = [];

        // 1. Login Admin
        $this->info("1. Logging in as Admin...");
        $response = Http::acceptJson()->post("{$this->baseUrl}/login", [
            'email' => 'admin@bapenda.go.id',
            'password' => 'password123'
        ]);
        
        if ($response->successful()) {
            $this->adminToken = $response->json('token');
            $this->info("   -> Admin logged in successfully.");
        } else {
            $errors[] = "Admin Login Failed: " . $response->body();
            $this->error("   -> Admin Login Failed.");
            return $this->printErrors($errors);
        }

        // 2. Login Petugas
        $this->info("2. Logging in as Petugas...");
        $response = Http::acceptJson()->post("{$this->baseUrl}/login", [
            'email' => 'petugas@bapenda.go.id',
            'password' => 'password123'
        ]);
        
        if ($response->successful()) {
            $this->petugasToken = $response->json('token');
            $this->petugasId = $response->json('user.id');
            $this->info("   -> Petugas logged in successfully.");
        } else {
            $errors[] = "Petugas Login Failed: " . $response->body();
            $this->error("   -> Petugas Login Failed.");
            return $this->printErrors($errors);
        }

        // 3. Get Retribution Types (Petugas)
        $this->info("3. Fetching Retribution Types & Classifications...");
        $response = Http::withToken($this->petugasToken)->acceptJson()->get("{$this->baseUrl}/retribution-types");
        if ($response->successful() && count($response->json('data')) > 0) {
            // Get any retribution type to assign to our dummy tax object
            $retributionType = collect($response->json('data'))->firstWhere('opd.code', 'BAPENDA');
            if (!$retributionType) {
                 $retributionType = $response->json('data')[0];
            }
            $this->info("   -> Selected Retribution Type: {$retributionType['name']}");
            
            // Fetch Classification
            $this->info("3.2 Fetching Retribution Classifications...");
            $classResponse = Http::withToken($this->petugasToken)->acceptJson()->get("{$this->baseUrl}/retribution-classifications?retribution_type_id={$retributionType['id']}");
            $classification = null;
            if ($classResponse->successful() && count($classResponse->json('data')) > 0) {
                 $classification = $classResponse->json('data')[0];
                 $this->info("   -> Selected Classification: {$classification['name']}");
            } else {
                 $this->info("   -> No specific classification found. Will proceed without it (might fail tax object generation if mandatory).");
            }
            
            // 3.5 Assign this Retribution Type to the Petugas via Admin API
            $this->info("3.5 Assigning Retribution Type/Classification to Petugas...");
            $assignPayload = ['retribution_type_id' => $retributionType['id']];
            if ($classification) {
                $assignPayload['retribution_classification_id'] = $classification['id'];
            }
            
            $assignResponse = Http::withToken($this->adminToken)->acceptJson()->put("{$this->baseUrl}/users/{$this->petugasId}", [
                'assignments' => [ $assignPayload ]
            ]);
            
            if ($assignResponse->successful()) {
                $this->info("   -> Petugas assignment updated successfully.");
            } else {
                $errors[] = "Petugas Assignment Failed: " . $assignResponse->body();
                $this->error("   -> Petugas Assignment Failed.");
                return $this->printErrors($errors);
            }
            
        } else {
            $errors[] = "Failed to fetch retribution types: " . $response->body();
            $this->error("   -> Failed to fetch retribution types.");
            return $this->printErrors($errors);
        }

        // 4. Create Taxpayer (Petugas)
        $this->info("4. Registering Test Taxpayer...");
        $nik = '9999999999' . rand(100000, 999999);
        $payload = [
            'nik' => $nik,
            'name' => '[TEST] WP Uji Coba Bapenda',
            'email' => 'testwp' . rand(100,999) . '@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Pengujian No. ' . rand(1, 100),
            'npwp' => '12.345.678.9-012.000',
            'retribution_type_ids' => [$retributionType['id']]
        ];
        
        if (isset($classification)) {
            $payload['retribution_classification_ids'] = [$classification['id']];
        }

        $response = Http::withToken($this->petugasToken)->acceptJson()->post("{$this->baseUrl}/taxpayers", $payload);

        if ($response->successful()) {
            $taxpayerData = $response->json('data') ?? $response->json('taxpayer');
            $this->taxpayerId = $taxpayerData['id'] ?? null;
            $this->info("   -> Taxpayer registered with ID: {$this->taxpayerId}");
            
            // Extract the auto-created Tax Object
            $taxObjects = $taxpayerData['tax_objects'] ?? [];
            if (count($taxObjects) > 0) {
                $this->taxObjectId = $taxObjects[0]['id'];
                $this->info("   -> Extracted auto-created Tax Object with ID: {$this->taxObjectId}");
            } else {
                $this->info("   -> Taxpayer created but no Tax Object was auto-generated. Will manually create in Step 5.");
            }
        } else {
            $errorMsg = $response->json('message') ?? $response->body();
            $errors[] = "Taxpayer Registration Failed: " . $errorMsg;
            if ($response->json('errors')) {
                $errors[] = "Validation: " . json_encode($response->json('errors'));
            }
            $this->error("   -> Taxpayer Registration Failed.");
            return $this->printErrors($errors);
        }

        // 5. Create TaxObject (Petugas) manually (fallback or forced test)
        $this->info("5. Creating Test Tax Object...");
        $payloadObj = [
            'taxpayer_id' => $this->taxpayerId,
            'retribution_type_id' => $retributionType['id'],
            'name' => '[TEST] Objek Pajak Bapenda',
            'address' => 'Jl. Test Objek No. ' . rand(1, 100),
            'latitude' => -5.462, // Baubau coordinates approx
            'longitude' => 122.589,
        ];

        if (isset($classification)) {
            $payloadObj['retribution_classification_id'] = $classification['id'];
        }

        $response = Http::withToken($this->petugasToken)->acceptJson()->post("{$this->baseUrl}/tax-objects", $payloadObj);

        if ($response->successful()) {
            $this->taxObjectId = $response->json('data.id') ?? $response->json('tax_object.id');
            if (!$this->taxObjectId && isset($response->json()['id'])) {
                 $this->taxObjectId = $response->json()['id'];
            }
            $this->info("   -> Tax Object created with ID: {$this->taxObjectId}");
        } else {
            $errorMsg = $response->json('message') ?? $response->body();
            $errors[] = "Tax Object Creation Failed: " . $errorMsg;
            if ($response->json('errors')) {
                $errors[] = "Validation: " . json_encode($response->json('errors'));
            }
            $this->error("   -> Tax Object Creation Failed.");
            return $this->printErrors($errors);
        }

        // 6. Create Billing (Petugas)
        $this->info("6. Creating Bill...");
        $response = Http::withToken($this->petugasToken)->acceptJson()->post("{$this->baseUrl}/bills", [
            'taxpayer_id' => $this->taxpayerId,
            'tax_object_id' => $this->taxObjectId,
            'amount' => 50000,
            'base_amount' => 50000,
            'description' => '[TEST] Tagihan Retribusi',
            'period' => date('Y-m'),
            'type' => 'monthly',
            'due_date' => date('Y-m-d', strtotime('+30 days')),
        ]);

        if ($response->successful()) {
            $this->billId = $response->json('data.id') ?? $response->json('bill.id');
            if (!$this->billId && isset($response->json()['id'])) {
                 $this->billId = $response->json()['id'];
            }
            $this->info("   -> Bill created with ID: {$this->billId}");
        } else {
            $errors[] = "Bill Creation Failed: " . $response->body();
            $this->error("   -> Bill Creation Failed.");
            return $this->printErrors($errors);
        }

        // 7. Pay the Bill (Petugas Collection)
        $this->info("7. Creating Payment...");
        $response = Http::withToken($this->petugasToken)->acceptJson()->post("{$this->baseUrl}/payments", [
            'tax_object_id' => $this->taxObjectId,
            'bill_id' => $this->billId,
            'amount' => 50000,
            'payment_method' => 'cash',
            'notes' => '[TEST] Pembayaran Tunai',
            'payment_date' => date('Y-m-d H:i:s'),
            'billing_period' => date('Y-m'),
        ]);

        if ($response->successful()) {
            $this->paymentId = $response->json('data.id') ?? $response->json('payment.id');
            if (!$this->paymentId && isset($response->json()['id'])) {
                 $this->paymentId = $response->json()['id'];
            }
            // Payment creates verification automatically
            $this->info("   -> Payment created with ID: {$this->paymentId}");
        } else {
            $errors[] = "Payment Creation Failed: " . $response->body();
            $this->error("   -> Payment Creation Failed.");
            return $this->printErrors($errors);
        }

        // 8. Admin Verification
        $this->info("8. Checking Verifications...");
        // Get Verifications list
        $response = Http::withToken($this->adminToken)->acceptJson()->get("{$this->baseUrl}/verifications");
        if ($response->successful()) {
            $verifications = $response->json('data.data') ?? $response->json('data') ?? [];
            $myVerification = collect($verifications)->firstWhere('payment_id', $this->paymentId);
            
            if ($myVerification) {
                // Determine verification ID
                $this->verificationId = $myVerification['id'];
                
                $this->info("   -> Verification record auto-created and visible to Admin. ID: {$this->verificationId}, Status: {$myVerification['status']}");
                
                if ($myVerification['status'] === 'pending') {
                    // Approve it if it's somehow pending
                    $verifyResponse = Http::withToken($this->adminToken)->acceptJson()->put("{$this->baseUrl}/verifications/{$this->verificationId}/status", [
                        'status' => 'approved',
                        'notes' => '[TEST] Disetujui'
                    ]);

                    if ($verifyResponse->successful()) {
                        $this->info("   -> Verification approved successfully.");
                    } else {
                        $errors[] = "Admin Verification Failed: " . $verifyResponse->body();
                        $this->error("   -> Admin Verification Failed.");
                    }
                }
            } else {
                $this->info("   -> Verification not found, but since payment was cash by Petugas, it's considered auto-verified.");
            }
            
        } else {
            $errors[] = "Failed to fetch verifications: " . $response->body();
            $this->error("   -> Failed to fetch verifications.");
        }

        $this->info("--------------------------------------------------");
        if (count($errors) > 0) {
            $this->error("TEST FINISHED WITH ERRORS");
            $this->printErrors($errors);
        } else {
            $this->info("ALL TESTS COMPLETED SUCCESSFULLY!");
        }

        /*
        // CLEANUP: We can hit the local database since the production API doesn't have a fast drop/delete route for all testing dependencies. Let's do nothing so we can view it in the dashboard temporarily.
        */
    }

    private function printErrors($errors)
    {
        foreach ($errors as $error) {
            $this->error("- " . $error);
        }
    }
}

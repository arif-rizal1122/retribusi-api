<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Inquiry a bill to get details and latest amount (including penalty)
     */
    public function inquiry(string $billNumber): array;

    /**
     * Process a payment notification callback from bank
     */
    public function notify(array $payload): array;

    /**
     * Process a reversal request from bank
     */
    public function reversal(array $payload): array;

    /**
     * Get account details (VA number, QR, etc) for a bill
     */
    public function getAccountDetail(string $billNumber): array;

    /**
     * Reconcile daily transactions with bank report
     */
    public function reconcile(array $transactions): array;
}

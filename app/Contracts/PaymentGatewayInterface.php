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
}

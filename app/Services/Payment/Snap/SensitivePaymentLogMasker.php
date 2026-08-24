<?php

namespace App\Services\Payment\Snap;

class SensitivePaymentLogMasker
{
    private const MASK = '[MASKED]';

    private const SENSITIVE_PARTS = [
        'authorization',
        'client_key',
        'client_secret',
        'password',
        'private_key',
        'public_key',
        'secret',
        'signature',
        'token',
        'api_key',
    ];

    public function mask(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $payload[$key] = self::MASK;
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->mask($value);
            }
        }

        return $payload;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }
}

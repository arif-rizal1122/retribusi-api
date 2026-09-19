<?php

namespace App\Services\Payment\Snap;

class BankSultraCitizenVaReadiness
{
    public function available(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function publicMessage(): string
    {
        return 'Virtual Account Bank Sultra belum tersedia. Gunakan transfer manual atau metode lain.';
    }

    public function unavailableReason(): ?string
    {
        if (! $this->boolConfig('snap.bank_sultra_va.enabled')) {
            return 'BANK_SULTRA_CITIZEN_VA_ENABLED belum diaktifkan.';
        }

        if (! $this->boolConfig('snap.bank_sultra_va.contract_confirmed')) {
            return 'Kontrak Virtual Account Bank Sultra untuk checkout citizen belum dikonfirmasi.';
        }

        return null;
    }

    private function boolConfig(string $key): bool
    {
        return filter_var(config($key, false), FILTER_VALIDATE_BOOLEAN);
    }
}

<?php

namespace App\Services\Payment\Snap;

class BankSultraAftReadiness
{
    private const PRODUCTS = [
        'direct_debit_h2h',
        'direct_debit_bifast',
    ];

    public function available(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function publicMessage(): string
    {
        return 'AFT Bank Sultra belum tersedia. Gunakan pencatatan manual sementara.';
    }

    public function unavailableReason(): ?string
    {
        if (! $this->boolConfig('snap.bank_sultra_aft.enabled')) {
            return 'BANK_SULTRA_AFT_ENABLED belum diaktifkan.';
        }

        $product = trim((string) config('snap.bank_sultra_aft.selected_product'));
        if (! in_array($product, self::PRODUCTS, true)) {
            return 'BANK_SULTRA_AFT_SELECTED_PRODUCT belum valid.';
        }

        $baseUrl = trim((string) config('snap.bank_sultra_aft.base_url'));
        if ($baseUrl === '' || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return 'BANK_SULTRA_SNAP_BASE_URL belum dikonfigurasi.';
        }

        if ($this->isLegacyPlaceholderUrl($baseUrl)) {
            return 'BANK_SULTRA_SNAP_BASE_URL masih memakai placeholder lama.';
        }

        $partnerId = trim((string) config('snap.partners.BANK_SULTRA.partner_id'));
        if ($partnerId === '') {
            return 'BANK_SULTRA_SNAP_PARTNER_ID belum dikonfigurasi.';
        }

        $clientKey = trim((string) config('snap.partners.BANK_SULTRA.client_key'));
        if ($clientKey === '') {
            return 'BANK_SULTRA_SNAP_CLIENT_KEY belum dikonfigurasi.';
        }

        $clientSecret = trim((string) config('snap.partners.BANK_SULTRA.client_secret'));
        if ($clientSecret === '') {
            return 'BANK_SULTRA_SNAP_CLIENT_SECRET belum dikonfigurasi.';
        }

        $channelId = trim((string) config('snap.partners.BANK_SULTRA.channel_id'));
        if ($channelId === '') {
            return 'BANK_SULTRA_SNAP_CHANNEL_ID belum dikonfigurasi.';
        }

        $signatureAlgorithm = strtolower((string) config('snap.partners.BANK_SULTRA.signature_algorithm', 'rsa_sha256'));
        if (! in_array($signatureAlgorithm, ['rsa_sha256', 'hmac_sha512'], true)) {
            return 'BANK_SULTRA_SNAP_SIGNATURE_ALGORITHM belum valid.';
        }

        if ($signatureAlgorithm === 'hmac_sha512') {
            $signatureSecret = trim((string) config('snap.partners.BANK_SULTRA.signature_secret'));
            if ($signatureSecret === '') {
                return 'BANK_SULTRA_SNAP_SIGNATURE_SECRET belum dikonfigurasi.';
            }

            if ($this->looksLikeKeyMaterial($signatureSecret)) {
                return 'BANK_SULTRA_SNAP_SIGNATURE_SECRET terlihat seperti key material. Gunakan path key yang sesuai.';
            }
        } else {
            $privateKeyPath = trim((string) config('snap.partners.BANK_SULTRA.private_key_path'));
            if ($privateKeyPath === '') {
                return 'BANK_SULTRA_SNAP_PRIVATE_KEY_PATH belum dikonfigurasi.';
            }

            if (! is_readable($privateKeyPath)) {
                return 'Private key Bank Sultra tidak dapat dibaca aplikasi.';
            }
        }

        $timeoutSeconds = (int) config('snap.bank_sultra_aft.timeout_seconds', 15);
        if ($timeoutSeconds < 1 || $timeoutSeconds > 60) {
            return 'BANK_SULTRA_AFT_TIMEOUT_SECONDS harus di antara 1 dan 60.';
        }

        $sourceAccountNo = preg_replace('/\D/', '', (string) config('snap.bank_sultra_aft.source_account_no'));
        if ($sourceAccountNo === '' || $this->isKnownDummyAccount($sourceAccountNo)) {
            return 'BANK_SULTRA_AFT_SOURCE_ACCOUNT_NO belum dikonfigurasi resmi.';
        }

        $destinationAccountNo = preg_replace('/\D/', '', (string) config('snap.bank_sultra_aft.destination_account_no'));
        if ($destinationAccountNo === '' || $this->isKnownDummyAccount($destinationAccountNo)) {
            return 'BANK_SULTRA_AFT_DESTINATION_ACCOUNT_NO belum dikonfigurasi resmi.';
        }

        if (! app()->environment(['local', 'testing']) && count((array) config('snap.bank_sultra_aft.allowed_ips', [])) === 0) {
            return 'BANK_SULTRA_AFT_ALLOWED_IPS belum dikonfigurasi.';
        }

        return null;
    }

    private function boolConfig(string $key): bool
    {
        return filter_var(config($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    private function isLegacyPlaceholderUrl(string $url): bool
    {
        $normalized = strtolower(rtrim($url, '/'));

        return str_contains($normalized, '/snap/v1.0/direct-debit/payment')
            || str_contains($normalized, '/direct-debit/payment');
    }

    private function isKnownDummyAccount(string $accountNo): bool
    {
        return in_array($accountNo, ['1234567890', '0987654321'], true);
    }

    private function looksLikeKeyMaterial(string $value): bool
    {
        return str_contains($value, 'BEGIN PUBLIC KEY')
            || str_contains($value, 'BEGIN PRIVATE KEY')
            || str_contains($value, 'BEGIN RSA PRIVATE KEY');
    }
}

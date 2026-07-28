<?php

namespace App\Services\Payment\Snap;

class SnapBrivaReadiness
{
    public function available(): bool
    {
        return $this->unavailableReason() === null;
    }

    public function publicMessage(): string
    {
        return 'Channel BRIVA belum tersedia. Gunakan metode pembayaran lain.';
    }

    public function unavailableReason(): ?string
    {
        if (! $this->boolConfig('snap.briva.enabled')) {
            return 'BRI_SNAP_ENABLED belum diaktifkan.';
        }

        $partnerId = trim((string) config('snap.partners.BRI.partner_id'));
        if ($partnerId === '' || $partnerId === 'test-partner') {
            return 'BRI_SNAP_PARTNER_ID belum dikonfigurasi.';
        }

        $clientKey = trim((string) config('snap.partners.BRI.client_key'));
        if ($clientKey === '' || $clientKey === 'test-client') {
            return 'BRI_SNAP_CLIENT_KEY belum dikonfigurasi.';
        }

        $signatureAlgorithm = strtolower((string) config('snap.partners.BRI.signature_algorithm', 'rsa_sha256'));
        if ($signatureAlgorithm === 'hmac_sha512') {
            if (trim((string) config('snap.partners.BRI.signature_secret')) === '') {
                return 'BRI_SNAP_SIGNATURE_SECRET belum dikonfigurasi.';
            }
        } else {
            $publicKey = trim((string) config('snap.partners.BRI.public_key'));
            $publicKeyPath = trim((string) config('snap.partners.BRI.public_key_path'));
            if ($publicKey === '' && $publicKeyPath === '') {
                return 'Public key BRI belum dikonfigurasi.';
            }

            if ($publicKeyPath !== '' && ! is_readable($publicKeyPath)) {
                return 'Public key BRI tidak dapat dibaca aplikasi.';
            }
        }

        $prefix = preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
        $length = (int) config('snap.briva.va_length', 18);
        if ($prefix === '' || $length <= 0 || strlen($prefix) >= $length) {
            return 'Prefix atau panjang VA BRIVA belum valid.';
        }

        if ($prefix === '777' && ! $this->allowsLocalSmoke()) {
            return 'Prefix VA dummy hanya boleh dipakai untuk local smoke.';
        }

        if (! $this->allowsLocalSmoke() && count((array) config('snap.allowed_ips', [])) === 0) {
            return 'SNAP_ALLOWED_IPS belum dikonfigurasi.';
        }

        return null;
    }

    private function boolConfig(string $key): bool
    {
        return filter_var(config($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    private function allowsLocalSmoke(): bool
    {
        return app()->environment(['local', 'testing']);
    }
}

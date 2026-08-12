<?php

namespace App\Services\Payment;

use InvalidArgumentException;

/**
 * VaNumberGenerator — Generator nomor Virtual Account (VA) M-PAD format hybrid SNAP/ASPI.
 *
 * Skema (mengacu skema_va_mpad.pdf):
 *   virtualAccountNo = partnerServiceId
 *                      + [1 digit klasifikasi PAD]
 *                      + [2 digit kode layanan]
 *                      + [ID transaksi numerik, zero-pad hingga total VA = 15 digit]
 *
 *   - partnerServiceId : prefix/ID partner bank, contoh '19960' (dipakai apa adanya;
 *     pada wire format SNAP field ini 8 karakter dan di-left-pad spasi oleh driver
 *     saat mengirim request ke bank — lihat BankBTNDriver::getAccountDetail()).
 *   - klasifikasi PAD  : 1 = Pajak, 2 = Retribusi, 3 = Aset.
 *   - kode layanan     : 01 = Reklame, 02 = Parkir, dst (rentang 01-99).
 *   - ID transaksi     : numerik, di-zero-pad ke kiri; lebar menyesuaikan agar total
 *     VA tepat 15 digit (dengan partnerServiceId 5 karakter → lebar 7 digit).
 *
 * Contoh:
 *   - Pajak Reklame    : generate('19960', 1, 2, 333) => '199601020000333' (15 digit)
 *   - Retribusi Parkir : generate('19960', 2, 1, 444) => '199602010000444' (15 digit)
 */
class VaNumberGenerator
{
    /** Panjang total nomor VA (15 digit). */
    public const TOTAL_LENGTH = 15;

    public const CLASSIFICATION_PAJAK = 1;
    public const CLASSIFICATION_RETRIBUSI = 2;
    public const CLASSIFICATION_ASET = 3;

    public const CLASSIFICATION_LENGTH = 1;
    public const SERVICE_CODE_LENGTH = 2;
    public const MIN_TRANSACTION_ID_LENGTH = 7;
    public const MAX_TRANSACTION_ID_LENGTH = 10;

    /**
     * Generate nomor VA sesuai skema hybrid SNAP/ASPI.
     *
     * @throws InvalidArgumentException bila klasifikasi/kode layanan/ID transaksi tidak valid
     *         atau kombinasi panjang tidak dapat menghasilkan VA 15 digit.
     */
    public function generate(string $partnerServiceId, int $classification, int $serviceCode, int $transactionId): string
    {
        $this->assertClassification($classification);
        $this->assertServiceCode($serviceCode);

        if ($transactionId < 0) {
            throw new InvalidArgumentException("transactionId tidak boleh negatif: {$transactionId}.");
        }

        // Toleransi spasi tak disengaja di sekitar prefix (field SNAP 8 karakter, contoh '   19960').
        $partner = trim($partnerServiceId);
        if ($partner === '' || strlen($partner) > 8) {
            throw new InvalidArgumentException(
                "partnerServiceId harus 1-8 karakter (diterima: '{$partnerServiceId}')."
            );
        }

        // Lebar digit transaksi agar total tepat 15 digit: 15 - partner - 1 (klasifikasi) - 2 (kode layanan).
        $transactionLength = self::TOTAL_LENGTH
            - strlen($partner)
            - self::CLASSIFICATION_LENGTH
            - self::SERVICE_CODE_LENGTH;

        if ($transactionLength < self::MIN_TRANSACTION_ID_LENGTH || $transactionLength > self::MAX_TRANSACTION_ID_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Kombinasi partnerServiceId (%d karakter) menghasilkan lebar ID transaksi %d digit; '
                    . 'harus %d-%d digit agar total VA %d digit.',
                strlen($partner),
                $transactionLength,
                self::MIN_TRANSACTION_ID_LENGTH,
                self::MAX_TRANSACTION_ID_LENGTH,
                self::TOTAL_LENGTH
            ));
        }

        if (strlen((string) $transactionId) > $transactionLength) {
            throw new InvalidArgumentException(sprintf(
                'transactionId %d tidak muat dalam %d digit pada skema VA %d digit.',
                $transactionId,
                $transactionLength,
                self::TOTAL_LENGTH
            ));
        }

        $va = $partner
            . (string) $classification
            . str_pad((string) $serviceCode, self::SERVICE_CODE_LENGTH, '0', STR_PAD_LEFT)
            . str_pad((string) $transactionId, $transactionLength, '0', STR_PAD_LEFT);

        if (strlen($va) !== self::TOTAL_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Panjang VA harus %d digit, diperoleh %d.',
                self::TOTAL_LENGTH,
                strlen($va)
            ));
        }

        return $va;
    }

    /**
     * Parse nomor VA kembali menjadi komponennya.
     *
     * @return array{
     *     partner_service_id: string,
     *     classification: int,
     *     service_code: int,
     *     transaction_id: int
     * }
     *
     * @throws InvalidArgumentException bila panjang/format VA tidak sesuai skema.
     */
    public function parse(string $vaNumber): array
    {
        $va = trim($vaNumber);

        if (strlen($va) !== self::TOTAL_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Panjang VA harus %d digit, diperoleh %d.',
                self::TOTAL_LENGTH,
                strlen($va)
            ));
        }

        // Batas panjang partnerServiceId sehingga lebar ID transaksi berada di rentang 7-10 digit:
        // partnerLength = 15 - 3 - transactionLength  =>  2 (trx 10 digit) s.d. 5 (trx 7 digit).
        $minPartnerLength = self::TOTAL_LENGTH
            - self::CLASSIFICATION_LENGTH
            - self::SERVICE_CODE_LENGTH
            - self::MAX_TRANSACTION_ID_LENGTH;
        $maxPartnerLength = self::TOTAL_LENGTH
            - self::CLASSIFICATION_LENGTH
            - self::SERVICE_CODE_LENGTH
            - self::MIN_TRANSACTION_ID_LENGTH;

        for ($partnerLength = $minPartnerLength; $partnerLength <= $maxPartnerLength; $partnerLength++) {
            $classification = (int) substr($va, $partnerLength, self::CLASSIFICATION_LENGTH);
            $serviceCode = (int) substr($va, $partnerLength + self::CLASSIFICATION_LENGTH, self::SERVICE_CODE_LENGTH);

            if (! in_array($classification, [self::CLASSIFICATION_PAJAK, self::CLASSIFICATION_RETRIBUSI, self::CLASSIFICATION_ASET], true)) {
                continue;
            }

            if ($serviceCode < 1 || $serviceCode > 99) {
                continue;
            }

            return [
                'partner_service_id' => substr($va, 0, $partnerLength),
                'classification' => $classification,
                'service_code' => $serviceCode,
                'transaction_id' => (int) substr(
                    $va,
                    $partnerLength + self::CLASSIFICATION_LENGTH + self::SERVICE_CODE_LENGTH
                ),
            ];
        }

        throw new InvalidArgumentException(sprintf(
            "VA '%s' tidak sesuai skema VA M-PAD (partnerServiceId + klasifikasi + kode layanan + ID transaksi).",
            $vaNumber
        ));
    }

    /**
     * Validasi klasifikasi PAD: 1 = Pajak, 2 = Retribusi, 3 = Aset.
     */
    private function assertClassification(int $classification): void
    {
        if (! in_array($classification, [
            self::CLASSIFICATION_PAJAK,
            self::CLASSIFICATION_RETRIBUSI,
            self::CLASSIFICATION_ASET,
        ], true)) {
            throw new InvalidArgumentException(sprintf(
                'Klasifikasi PAD tidak valid: %d. Harus 1 (Pajak), 2 (Retribusi), atau 3 (Aset).',
                $classification
            ));
        }
    }

    /**
     * Validasi kode layanan: rentang 1-99 (01 = Reklame, 02 = Parkir, dst).
     */
    private function assertServiceCode(int $serviceCode): void
    {
        if ($serviceCode < 1 || $serviceCode > 99) {
            throw new InvalidArgumentException(sprintf(
                'Kode layanan tidak valid: %d. Harus antara 1-99.',
                $serviceCode
            ));
        }
    }
}

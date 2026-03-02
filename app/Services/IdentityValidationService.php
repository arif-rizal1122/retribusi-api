<?php

namespace App\Services;

class IdentityValidationService
{
    /**
     * Mock validation for NIK against a "Dukcapil" registry
     * @param string $nik
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateNik($nik)
    {
        // Basic format check
        if (!preg_match('/^\d{16}$/', $nik)) {
            return ['valid' => false, 'message' => 'Format NIK tidak valid. Harus 16 digit angka.'];
        }

        // Mock external API validation: 
        // Let's pretend any NIK ending in '999' is rejected by Dukcapil.
        if (str_ends_with($nik, '999')) {
            return ['valid' => false, 'message' => 'NIK tidak terdaftar di sistem Dukcapil.'];
        }

        return ['valid' => true, 'message' => 'NIK Valid'];
    }

    /**
     * Mock validation for NPWP/NPWPD against a "DJP/Bapenda" registry
     * @param string $npwp
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateNpwp($npwp)
    {
        // Strip out non-numeric characters for mock
        $cleanNpwp = preg_replace('/[^0-9]/', '', $npwp);

        // Usually NPWP is 15 or 16 digits
        if (strlen($cleanNpwp) < 15 || strlen($cleanNpwp) > 16) {
            return ['valid' => false, 'message' => 'Format NPWP tidak valid. Biasanya 15 atau 16 digit.'];
        }

        // Mock external API validation
        if (str_ends_with($cleanNpwp, '0000')) {
            return ['valid' => false, 'message' => 'NPWP tidak valid atau sudah tidak aktif (DJP).'];
        }

        return ['valid' => true, 'message' => 'NPWP Valid'];
    }
}

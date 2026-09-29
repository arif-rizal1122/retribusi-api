<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\SignedDocument;
use Illuminate\Support\Facades\Log;

class AutoTteHookService
{
    protected TTEService $tteService;

    public function __construct(TTEService $tteService)
    {
        $this->tteService = $tteService;
    }

    /**
     * Auto-TTE Hook: Executed immediately when a bill is settled (marked as LUNAS / PAID)
     */
    public function handlePaidBill(Bill $bill, string $paymentMethod = 'CASH', ?string $ntpd = null): ?SignedDocument
    {
        try {
            $bill->loadMissing(['retributionType', 'taxpayer', 'taxObject', 'payments', 'opd']);

            // Idempotency: Check if already signed
            $existing = SignedDocument::where('document_type', get_class($bill))
                ->where('document_id', $bill->id)
                ->where('status', 'signed')
                ->first();

            if ($existing) {
                return $existing;
            }

            $opdCode = $bill->opd?->code ?? 'BAPENDA';
            $typeName = strtolower($bill->retributionType?->name ?? '');
            $category = strtolower($bill->retributionType?->category ?? '');
            $isAsset = str_contains($typeName, 'sewa') 
                || str_contains($typeName, 'alat') 
                || str_contains($typeName, 'tanah') 
                || str_contains($typeName, 'bangunan')
                || str_contains($typeName, 'gedung')
                || str_contains($category, 'aset')
                || !empty($bill->asset_rental_id);

            // 1. Asset BMD: Apply Transitional Numeric Token OPD (Solusi Transisi Legal)
            if ($isAsset) {
                $tenantName = $bill->taxpayer?->name ?? 'Penyewa Terdaftar';
                $signedDoc = $this->tteService->signAssetWithTransitionToken(
                    documentable: $bill,
                    opdCode: $opdCode,
                    tenantIdentity: $tenantName,
                    notes: "Pelunasan Sewa Aset Daerah via {$paymentMethod}. NTPD: {$ntpd}"
                );

                Log::info("AutoTteHook: Asset bill {$bill->bill_number} signed with transitional OPD token: {$signedDoc->signature_nip}");
                return $signedDoc;
            }

            // 2. Pajak Daerah vs Retribusi Daerah vs PBB-P2
            $isPbb = str_contains($typeName, 'pbb') || str_contains($category, 'pbb');
            $isTax = str_contains($category, 'pajak') || str_contains($typeName, 'bphtb') || str_contains($typeName, 'pbjt') || str_contains($typeName, 'reklame');

            $docType = $isPbb ? 'STTS' : ($isTax ? 'SSPD' : 'SSRD');
            $docNumber = "{$docType}/{$opdCode}/" . date('Y/m/') . ($bill->bill_number ?? $bill->id);

            $signedDoc = $this->tteService->signDocument(
                documentable: $bill,
                signer: null, // Institutional electronic seal
                notes: "Pengesahan Bukti Lunas Otomatis ({$docType}) via {$paymentMethod}. NTPD: " . ($ntpd ?? 'NTPD-' . date('Ymd') . '-' . $bill->id),
                signatureType: 'ELECTRONIC_SEAL',
                customDocNumber: $docNumber,
                opdId: $bill->opd_id
            );

            Log::info("AutoTteHook: Bill {$bill->bill_number} automatically sealed as {$docType} ber-TTE: {$signedDoc->document_number}");

            // Trigger NOTIF-WRG-006 (Bukti Lunas Sah e-SSPD / Segel Elektronik BSrE)
            try {
                $phone = $bill->taxpayer?->phone;
                if ($phone) {
                    $officialNtpd = $ntpd ?? ('NTPD-'.date('Ymd').'-'.$bill->id);
                    $notifService = app(NotificationService::class);
                    $notifService->notifyPaymentSuccess(
                        phone: $phone,
                        namaWarga: $bill->taxpayer?->name ?: 'Wajib Pajak',
                        ntpd: $officialNtpd,
                        nomorSkrd: $bill->bill_number ?: (string)$bill->id,
                        namaLayanan: $bill->retributionType?->name ?: ($bill->taxObject?->name ?: 'Layanan M-PAD'),
                        nominal: $bill->total_amount,
                        waktuBayar: now()->timezone('Asia/Makassar')->format('d/m/Y H:i'),
                        namaChannelBank: $paymentMethod,
                        user: null,
                        paymentId: $bill->id,
                        async: false
                    );
                }
            } catch (\Throwable $notifEx) {
                Log::warning("AutoTteHook: Failed to send NOTIF-WRG-006: " . $notifEx->getMessage());
            }

            return $signedDoc;
        } catch (\Throwable $e) {
            Log::error("AutoTteHook Exception for Bill {$bill->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        }
    }
}

<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Taxpayer;
use Illuminate\Support\Facades\Log;

class PaymentDisputeService
{
    public function __construct(
        private WhatsAppService $whatsAppService
    ) {
    }

    /**
     * Create an automated dispute ticket (Complaint) and alert Bidang Penagihan.
     *
     * @param  Taxpayer|null  $taxpayer
     * @param  Bill|null  $bill
     * @param  array  $data  [claimed_amount, reason, proof_url, bank_reference, phone, name, email]
     * @return Complaint
     */
    public function createDisputeTicket(?Taxpayer $taxpayer, ?Bill $bill, array $data): Complaint
    {
        $name = $data['name'] ?? $taxpayer?->name ?? 'Wajib Pajak';
        $phone = $data['phone'] ?? $taxpayer?->phone ?? null;
        $email = $data['email'] ?? $taxpayer?->email ?? null;

        $billNumber = $bill?->bill_number ?? ($data['bill_number'] ?? 'N/A');
        $claimedAmount = (float) ($data['claimed_amount'] ?? $bill?->amount ?? 0);
        $formattedAmount = number_format($claimedAmount, 0, ',', '.');
        $reason = $data['reason'] ?? 'Wajib Pajak mengklaim telah membayar namun status belum terupdate/terjadi selisih.';
        $bankReference = $data['bank_reference'] ?? 'N/A';
        $proofUrl = $data['proof_url'] ?? null;

        $complaintText = "Dispute Pembayaran untuk Tagihan [{$billNumber}].\n"
            . "Nominal Diklaim: Rp {$formattedAmount}\n"
            . "Referensi/Jurnal Bank: {$bankReference}\n"
            . "Uraian Masalah: {$reason}";

        $attachments = array_merge($data['metadata'] ?? [], array_filter([
            'bill_id' => $bill?->id,
            'bill_number' => $billNumber,
            'claimed_amount' => $claimedAmount,
            'bank_reference' => $bankReference,
            'proof_url' => $proofUrl,
            'auto_generated' => true,
            'dispute_timestamp' => now()->toIso8601String(),
        ]));

        $complaint = Complaint::create([
            'taxpayer_id' => $taxpayer?->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'category' => 'dispute_pembayaran',
            'complaint_text' => $complaintText,
            'attachments' => $attachments,
            'status' => 'pending',
            'admin_notes' => 'Tiket dispute dibuat otomatis oleh sistem. Menunggu rekonsiliasi dan verifikasi kas daerah.',
        ]);

        // Dispatch alert to Bidang Penagihan
        $penagihanWa = config('services.bapenda.penagihan_wa');
        if ($penagihanWa) {
            $alertMessage = "🚨 [ALERT DISPUTE PEMBAYARAN M-PAD]\n\n"
                . "Telah diajukan klaim dispute tagihan:\n"
                . "• No. Tagihan: {$billNumber}\n"
                . "• Wajib Pajak: {$name}\n"
                . "• Nominal: Rp {$formattedAmount}\n"
                . "• Ref/Jurnal Bank: {$bankReference}\n"
                . "• No. Tiket: #{$complaint->id}\n\n"
                . "Mohon Bidang Penagihan segera melakukan audit rekonsiliasi korolari pada modul Rekonsiliasi.";

            try {
                $this->whatsAppService->sendMessage($penagihanWa, $alertMessage);
            } catch (\Exception $e) {
                Log::warning("Failed to send dispute alert WA to {$penagihanWa}: " . $e->getMessage());
            }
        }

        return $complaint;
    }
}

<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserDeviceToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public const TZ = 'Asia/Makassar';

    public function __construct(
        protected WaGatewayService $waGateway,
    ) {}

    /**
     * Dispatch notification by template code
     *
     * @param string $templateCode Template code (e.g. 'NOTIF-WRG-001')
     * @param array $payload Variables to interpolate in template
     * @param User|null $recipientUser Optional User recipient
     * @param string|null $overridePhone Optional phone number override
     * @param string|null $referenceType Reference entity type (e.g. 'service_applications')
     * @param int|string|null $referenceId Reference entity ID
     * @param int|null $taxpayerId Taxpayer ID if citizen
     * @return array Results per channel
     */
    public function dispatch(
        string $templateCode,
        array $payload = [],
        ?User $recipientUser = null,
        ?string $overridePhone = null,
        ?string $referenceType = null,
        $referenceId = null,
        ?int $taxpayerId = null
    ): array {
        $template = NotificationTemplate::where('code', $templateCode)->first();

        if (!$template || !$template->is_active) {
            Log::warning("NotificationDispatcher: Template {$templateCode} not found or inactive.");
            return ['success' => false, 'error' => 'Template not found or inactive'];
        }

        // Resolve phone
        $phone = $overridePhone;
        if (blank($phone) && $recipientUser) {
            $phone = $recipientUser->phone;
        }

        // Normalize payload aliases
        $payload = self::normalizePayload($templateCode, $payload);

        // Build Idempotency Key
        $idempotencyKey = $this->generateIdempotencyKey(
            $templateCode,
            $referenceType,
            $referenceId,
            $phone,
            $recipientUser?->id
        );

        // Check if already dispatched successfully
        $existingLog = NotificationLog::where('idempotency_key', $idempotencyKey)
            ->where('status', 'success')
            ->first();

        if ($existingLog) {
            Log::info("NotificationDispatcher: Skipped duplicate notification {$idempotencyKey}");
            return ['success' => true, 'duplicate' => true, 'log_id' => $existingLog->id];
        }

        $results = [];
        $channels = is_array($template->channels) ? $template->channels : [];

        // 1. WHATSAPP CHANNEL
        if (in_array('whatsapp', $channels) && !blank($template->wa_template) && !blank($phone)) {
            $results['whatsapp'] = $this->dispatchWhatsApp(
                $template,
                $payload,
                $phone,
                $recipientUser,
                $referenceType,
                $referenceId,
                $taxpayerId,
                $idempotencyKey
            );
        }

        // 2. PUSH NOTIFICATION CHANNEL
        if (in_array('push', $channels) && $recipientUser) {
            $results['push'] = $this->dispatchPush(
                $template,
                $payload,
                $recipientUser,
                $referenceType,
                $referenceId,
                $taxpayerId,
                $idempotencyKey . ':push'
            );
        }

        // 3. IN-APP NOTIFICATION CHANNEL
        if (in_array('in_app', $channels) && $recipientUser) {
            $results['in_app'] = $this->dispatchInApp(
                $template,
                $payload,
                $recipientUser,
                $referenceType,
                $referenceId,
                $taxpayerId,
                $idempotencyKey . ':in_app'
            );
        }

        return [
            'success' => true,
            'template' => $templateCode,
            'channels' => $results,
        ];
    }

    /**
     * Dispatch WhatsApp message
     */
    protected function dispatchWhatsApp(
        NotificationTemplate $template,
        array $payload,
        string $phone,
        ?User $user,
        ?string $refType,
        $refId,
        ?int $taxpayerId,
        string $idempotencyKey
    ): array {
        // Step 1: Interpolate values
        $text = SpintaxService::interpolate($template->wa_template, $payload);
        // Step 2: Apply spintax variations
        $finalMessage = SpintaxService::spin($text);

        $sent = $this->waGateway->sendMessage($phone, $finalMessage, $template->priority ?? 'medium');

        $status = $sent ? 'success' : 'failed';
        $now = $sent ? Carbon::now(self::TZ) : null;

        $log = NotificationLog::create([
            'taxpayer_id' => $taxpayerId,
            'user_id' => $user?->id,
            'phone' => $phone,
            'channel' => 'whatsapp',
            'type' => $template->code,
            'template_code' => $template->code,
            'reference_type' => $refType,
            'reference_id' => $refId ? (int)$refId : null,
            'message_preview' => mb_substr($finalMessage, 0, 500),
            'status' => $status,
            'idempotency_key' => $idempotencyKey,
            'sent_at' => $now,
            'delivered_at' => $now,
        ]);

        return [
            'sent' => $sent,
            'log_id' => $log->id,
        ];
    }

    /**
     * Dispatch Push notification
     */
    protected function dispatchPush(
        NotificationTemplate $template,
        array $payload,
        User $user,
        ?string $refType,
        $refId,
        ?int $taxpayerId,
        string $idempotencyKey
    ): array {
        $title = SpintaxService::interpolate($template->push_title ?? $template->label, $payload);
        $body = SpintaxService::interpolate($template->push_body ?? '', $payload);

        $tokens = UserDeviceToken::where('user_id', $user->id)->get();

        $log = NotificationLog::create([
            'taxpayer_id' => $taxpayerId,
            'user_id' => $user->id,
            'phone' => $user->phone,
            'channel' => 'push',
            'type' => $template->code,
            'template_code' => $template->code,
            'reference_type' => $refType,
            'reference_id' => $refId ? (int)$refId : null,
            'message_preview' => "[PUSH: {$title}] " . mb_substr($body, 0, 400),
            'status' => $tokens->isNotEmpty() ? 'success' : 'queued',
            'idempotency_key' => $idempotencyKey,
            'sent_at' => Carbon::now(self::TZ),
        ]);

        return [
            'tokens_count' => $tokens->count(),
            'log_id' => $log->id,
        ];
    }

    /**
     * Dispatch In-App / WebSocket Notification
     */
    protected function dispatchInApp(
        NotificationTemplate $template,
        array $payload,
        User $user,
        ?string $refType,
        $refId,
        ?int $taxpayerId,
        string $idempotencyKey
    ): array {
        $body = SpintaxService::interpolate($template->push_body ?? $template->label, $payload);

        $log = NotificationLog::create([
            'taxpayer_id' => $taxpayerId,
            'user_id' => $user->id,
            'channel' => 'in_app',
            'type' => $template->code,
            'template_code' => $template->code,
            'reference_type' => $refType,
            'reference_id' => $refId ? (int)$refId : null,
            'message_preview' => mb_substr($body, 0, 500),
            'status' => 'success',
            'idempotency_key' => $idempotencyKey,
            'sent_at' => Carbon::now(self::TZ),
            'delivered_at' => Carbon::now(self::TZ),
        ]);

        return [
            'broadcast' => true,
            'log_id' => $log->id,
        ];
    }

    /**
     * Build SHA-256 idempotency key
     */
    protected function generateIdempotencyKey(
        string $templateCode,
        ?string $refType,
        $refId,
        ?string $phone,
        ?int $userId
    ): string {
        $components = [
            $templateCode,
            $refType ?? 'none',
            (string)($refId ?? 'none'),
            $phone ?? 'none',
            (string)($userId ?? 'none'),
        ];

        // Untuk OTP atau alert non-reference, tambahkan date bucket per jam/menit
        if (!$refId) {
            $components[] = Carbon::now(self::TZ)->format('Y-m-d-H');
        }

        return hash('sha256', implode(':', $components));
    }

    /**
     * Normalize synonymous variables across templates and callers
     */
    public static function normalizePayload(string $templateCode, array $payload): array
    {
        // Aliases mapping
        if (isset($payload['otp']) && !isset($payload['otp_code'])) {
            $payload['otp_code'] = $payload['otp'];
        }
        if (isset($payload['otp_code']) && !isset($payload['otp'])) {
            $payload['otp'] = $payload['otp_code'];
        }

        if (isset($payload['nama_pemohon']) && !isset($payload['nama_warga'])) {
            $payload['nama_warga'] = $payload['nama_pemohon'];
        }
        if (isset($payload['nama_warga']) && !isset($payload['nama_pemohon'])) {
            $payload['nama_pemohon'] = $payload['nama_warga'];
        }
        if (isset($payload['name']) && !isset($payload['nama_warga'])) {
            $payload['nama_warga'] = $payload['name'];
            $payload['nama_pemohon'] = $payload['name'];
        }

        if (isset($payload['application_no']) && !isset($payload['nomor_berkas'])) {
            $payload['nomor_berkas'] = $payload['application_no'];
        }

        if (isset($payload['catatan_revisi']) && !isset($payload['catatan_verifikator'])) {
            $payload['catatan_verifikator'] = $payload['catatan_revisi'];
        }
        if (isset($payload['catatan_verifikator']) && !isset($payload['catatan_singkat'])) {
            $payload['catatan_singkat'] = mb_substr($payload['catatan_verifikator'], 0, 50);
        }

        if (isset($payload['nominal']) && !isset($payload['nominal_rupiah'])) {
            $payload['nominal_rupiah'] = is_numeric($payload['nominal']) 
                ? number_format((float)$payload['nominal'], 0, ',', '.') 
                : (string)$payload['nominal'];
        }

        if (isset($payload['jatuh_tempo']) && !isset($payload['tanggal_jatuh_tempo'])) {
            $payload['tanggal_jatuh_tempo'] = $payload['jatuh_tempo'];
        }

        if (isset($payload['kode_billing']) && !isset($payload['kode_billing_snap'])) {
            $payload['kode_billing_snap'] = $payload['kode_billing'];
        }
        if (isset($payload['kode_snap']) && !isset($payload['kode_billing_snap'])) {
            $payload['kode_billing_snap'] = $payload['kode_snap'];
        }
        if (isset($payload['kode_billing_snap']) && !isset($payload['kode_snap'])) {
            $payload['kode_snap'] = $payload['kode_billing_snap'];
        }

        if (isset($payload['briva']) && !isset($payload['kode_briva_16_digit'])) {
            $payload['kode_briva_16_digit'] = $payload['briva'];
        }
        if (isset($payload['kode_briva']) && !isset($payload['kode_briva_16_digit'])) {
            $payload['kode_briva_16_digit'] = $payload['kode_briva'];
        }
        if (isset($payload['kode_briva_16_digit']) && !isset($payload['kode_briva'])) {
            $payload['kode_briva'] = $payload['kode_briva_16_digit'];
        }

        if (isset($payload['sisa_hari']) && !isset($payload['sisa_hari_lagi'])) {
            $payload['sisa_hari_lagi'] = (string)$payload['sisa_hari'];
        }

        if (isset($payload['ntpd']) && !isset($payload['nomor_ntpd'])) {
            $payload['nomor_ntpd'] = $payload['ntpd'];
        }

        if (isset($payload['nomor_ketetapan']) && !isset($payload['nomor_skrd'])) {
            $payload['nomor_skrd'] = $payload['nomor_ketetapan'];
        }

        if (isset($payload['total_kas_tunai']) && !isset($payload['total_kas_di_tangan'])) {
            $payload['total_kas_di_tangan'] = is_numeric($payload['total_kas_tunai']) 
                ? number_format((float)$payload['total_kas_tunai'], 0, ',', '.') 
                : (string)$payload['total_kas_tunai'];
        }

        if (isset($payload['va_setor']) && !isset($payload['nomor_va_setor'])) {
            $payload['nomor_va_setor'] = $payload['va_setor'];
        }

        return $payload;
    }
}

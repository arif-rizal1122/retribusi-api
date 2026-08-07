<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class H2HNotifierService
{
    protected string $botToken;
    protected string $chatId;
    protected bool $enabled;

    public function __construct()
    {
        $this->botToken = (string) config('services.h2h_notify.bot_token', '');
        $this->chatId   = (string) config('services.h2h_notify.chat_id', '846880203');
        $this->enabled  = (bool) config('services.h2h_notify.telegram_enabled', false);
    }

    /**
     * Kirim notifikasi status H2H ke admin via Telegram.
     * Default off: kalau MPAD_BOT_TOKEN kosong / H2H_NOTIFY_TELEGRAM != true,
     * cukup tulis Log::warning agar jejak tetap ada.
     */
    public function notifyAdmin(string $title, string $message): void
    {
        if (! $this->enabled || $this->botToken === '') {
            Log::warning("[H2HNotifier] Telegram nonaktif — {$title}: {$message}");

            return;
        }

        try {
            $text = "📢 *{$title}*\n{$message}";

            Http::timeout(15)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error('H2HNotifier Telegram Error', ['error' => $e->getMessage()]);
        }
    }
}

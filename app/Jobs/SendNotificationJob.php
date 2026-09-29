<?php

namespace App\Jobs;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public string $templateCode,
        public array $payload = [],
        public ?int $recipientUserId = null,
        public ?string $overridePhone = null,
        public ?string $referenceType = null,
        public mixed $referenceId = null,
        public ?int $taxpayerId = null
    ) {
        // Resolve queue priority based on template
        $template = NotificationTemplate::where('code', $this->templateCode)->first();
        $priority = $template?->priority ?? 'medium';

        $queueName = match ($priority) {
            'high' => 'notif-high',
            'low' => 'notif-low',
            default => 'notif-medium',
        };

        $this->onQueue($queueName);
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationDispatcher $dispatcher): void
    {
        $user = $this->recipientUserId ? User::find($this->recipientUserId) : null;

        $result = $dispatcher->dispatch(
            templateCode: $this->templateCode,
            payload: $this->payload,
            recipientUser: $user,
            overridePhone: $this->overridePhone,
            referenceType: $this->referenceType,
            referenceId: $this->referenceId,
            taxpayerId: $this->taxpayerId
        );

        Log::info("SendNotificationJob executed [{$this->templateCode}]", $result);
    }
}

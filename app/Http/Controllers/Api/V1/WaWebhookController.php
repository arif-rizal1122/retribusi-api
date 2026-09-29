<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WaInteractiveBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaWebhookController extends Controller
{
    public function __construct(
        protected WaInteractiveBotService $botService
    ) {}

    /**
     * Handle incoming WhatsApp message webhook from Baileys gateway
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPhone = $request->input('phone') ?? $request->input('number') ?? $request->input('from');
        $message = $request->input('message') ?? $request->input('text') ?? '';

        if (blank($rawPhone) || blank($message)) {
            return response()->json([
                'success' => false,
                'error' => 'Parameter phone and message are required',
            ], 400);
        }

        try {
            $result = $this->botService->handleIncoming((string)$rawPhone, (string)$message);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('WaWebhookController exception: ' . $e->getMessage(), [
                'phone' => $rawPhone,
                'message' => $message,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error processing incoming message',
            ], 500);
        }
    }
}

<?php

namespace App\Services\Payment\Snap;

use App\Models\SnapIdempotencyKey;
use App\Services\Payment\Snap\Exceptions\DuplicateSnapExternalIdException;
use App\Services\Payment\Snap\Exceptions\SnapPaymentException;
use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SnapIdempotencyService
{
    public function reserve(Request $request): SnapIdempotencyKey
    {
        $externalId = (string) $request->header('X-EXTERNAL-ID');

        if ($externalId === '') {
            throw SnapValidationException::missing('Bad Request. Missing X-EXTERNAL-ID.');
        }

        try {
            return SnapIdempotencyKey::create([
                'external_id' => $externalId,
                'endpoint' => $request->path(),
                'request_hash' => $this->hashRequest($request),
                'status' => 'processing',
                'expires_at' => now()->addMinutes((int) config('snap.idempotency_ttl_minutes', 1440)),
            ]);
        } catch (QueryException) {
            $existing = SnapIdempotencyKey::where('external_id', $externalId)->first();

            if (!$existing) {
                throw SnapValidationException::missing('Bad Request. Duplicate X-EXTERNAL-ID cannot be resolved.');
            }

            if ($existing->request_hash !== $this->hashRequest($request)) {
                throw new SnapPaymentException('4092400', 'Conflict. X-EXTERNAL-ID has different request payload.', 409);
            }

            throw new DuplicateSnapExternalIdException($existing);
        }
    }

    public function complete(SnapIdempotencyKey $record, array $response, int $statusCode): void
    {
        $record->update([
            'response_payload' => $response,
            'status_code' => $statusCode,
            'status' => 'completed',
        ]);
    }

    public function hashRequest(Request $request): string
    {
        return hash('sha256', $request->method() . ':' . $request->path() . ':' . $request->getContent());
    }
}

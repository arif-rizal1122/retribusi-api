<?php

namespace App\Services\Payment\Snap;

use Illuminate\Http\Request;

class SnapCanonicalRequest
{
    public function stringToSignFromRequest(Request $request): string
    {
        return $this->stringToSign(
            $request->method(),
            '/' . ltrim($request->path(), '/'),
            $this->canonicalBodyFromRaw($request->getContent()),
            (string) $request->header('X-TIMESTAMP')
        );
    }

    public function stringToSign(string $method, string $path, string $canonicalBody, string $timestamp): string
    {
        return strtoupper($method) . ':' . $path . ':' . strtolower(hash('sha256', $canonicalBody)) . ':' . $timestamp;
    }

    public function canonicalBodyFromRaw(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    public function canonicalBodyFromArray(array $body): string
    {
        return json_encode($body, JSON_UNESCAPED_SLASHES);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\Snap\Exceptions\DuplicateSnapExternalIdException;
use App\Services\Payment\Snap\Exceptions\SnapPaymentException;
use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use App\Services\Payment\Snap\SnapBrivaService;
use App\Services\Payment\Snap\SnapIdempotencyService;
use App\Services\Payment\Snap\SnapRequestValidator;
use App\Services\Payment\Snap\SnapResponseMapper;
use App\Services\Payment\Snap\SnapSecurityService;
use App\Services\Payment\Snap\SnapTokenService;
use App\Services\PaymentAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SnapBIController extends Controller
{
    public function __construct(
        private readonly SnapSecurityService $security,
        private readonly SnapTokenService $tokens,
        private readonly SnapIdempotencyService $idempotency,
        private readonly SnapRequestValidator $requests,
        private readonly SnapResponseMapper $responses,
        private readonly SnapBrivaService $briva,
        private readonly PaymentAuditService $audit
    ) {}

    public function getAccessToken(Request $request): JsonResponse
    {
        try {
            $this->security->validateAccessTokenRequest($request);
            $bankCode = $request->attributes->get('snap_bank_code');
            $token = $this->tokens->issue((string) $request->header('X-CLIENT-KEY'), (string) $bankCode);
            $response = $this->responses->accessToken($token['access_token'], $token['expires_in']);

            return $this->respondAndLog($request, $response, 200);
        } catch (SnapValidationException $e) {
            return $this->validationError($request, $e);
        } catch (\Throwable $e) {
            return $this->serverError($request, $e, '73');
        }
    }

    public function qrisNotify(Request $request): JsonResponse
    {
        try {
            $this->security->validateTransactionRequest($request, '52');
            $response = $this->responses->qrisNotify();

            return $this->respondAndLog($request, $response, 200);
        } catch (SnapValidationException $e) {
            return $this->validationError($request, $e);
        } catch (\Throwable $e) {
            return $this->serverError($request, $e, '52');
        }
    }

    public function brivaInquiry(Request $request): JsonResponse
    {
        try {
            $this->security->validateTransactionRequest($request, '24');
            $this->requests->validateInquiry($request);
            $idempotencyKey = $this->idempotency->reserve($request, '24');
            $virtualAccountData = $this->briva->inquiry($request);
            $response = $this->responses->brivaInquiry($virtualAccountData);

            $this->idempotency->complete($idempotencyKey, $response, 200);

            return $this->respondAndLog($request, $response, 200);
        } catch (DuplicateSnapExternalIdException $e) {
            return $this->duplicateExternalId($request, $e, '24');
        } catch (SnapValidationException $e) {
            return $this->validationError($request, $e);
        } catch (SnapPaymentException $e) {
            return $this->paymentError($request, $e);
        } catch (\Throwable $e) {
            return $this->serverError($request, $e, '24');
        }
    }

    public function brivaPayment(Request $request): JsonResponse
    {
        try {
            $this->security->validateTransactionRequest($request, '25');
            $this->requests->validatePayment($request);
            $idempotencyKey = $this->idempotency->reserve($request, '25');
            $virtualAccountData = $this->briva->payment($request);
            $response = $this->responses->brivaPayment($virtualAccountData);

            $this->idempotency->complete($idempotencyKey, $response, 200);

            return $this->respondAndLog($request, $response, 200);
        } catch (DuplicateSnapExternalIdException $e) {
            return $this->duplicateExternalId($request, $e, '25');
        } catch (SnapValidationException $e) {
            return $this->validationError($request, $e);
        } catch (SnapPaymentException $e) {
            return $this->paymentError($request, $e);
        } catch (\Throwable $e) {
            return $this->serverError($request, $e, '25');
        }
    }

    private function validationError(Request $request, SnapValidationException $exception): JsonResponse
    {
        return $this->respondAndLog(
            $request,
            $this->responses->error($exception->snapCode, $exception->snapMessage),
            $exception->httpStatus
        );
    }

    private function paymentError(Request $request, SnapPaymentException $exception): JsonResponse
    {
        return $this->respondAndLog(
            $request,
            $this->responses->error($exception->snapCode, $exception->snapMessage),
            $exception->httpStatus
        );
    }

    private function duplicateExternalId(Request $request, DuplicateSnapExternalIdException $exception, string $serviceCode): JsonResponse
    {
        if ($exception->record->status === 'completed' && $exception->record->response_payload) {
            return $this->respondAndLog(
                $request,
                $exception->record->response_payload,
                $exception->record->status_code ?? 200
            );
        }

        return $this->respondAndLog(
            $request,
            $this->responses->error("409{$serviceCode}00", 'Conflict'),
            409
        );
    }

    private function serverError(Request $request, \Throwable $exception, string $serviceCode): JsonResponse
    {
        Log::error('SNAP BI request failed.', [
            'path' => $request->path(),
            'message' => $exception->getMessage(),
        ]);

        return $this->respondAndLog(
            $request,
            $this->responses->error("500{$serviceCode}00", 'General Error'),
            500
        );
    }

    private function respondAndLog(Request $request, array $response, int $statusCode): JsonResponse
    {
        $this->audit->record($request, $response, null, $request->path(), $statusCode);

        return response()->json($response, $statusCode);
    }
}

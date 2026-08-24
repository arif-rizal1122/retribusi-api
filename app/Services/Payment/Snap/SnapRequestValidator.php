<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;

class SnapRequestValidator
{
    public function validateInquiry(Request $request): void
    {
        $this->requireStrings($request, [
            'partnerServiceId',
            'customerNo',
            'virtualAccountNo',
            'inquiryRequestId',
        ], '24');

        if ($request->has('amount')) {
            $this->validateAmount($request->input('amount'), 'amount', '24');
        }
    }

    public function validatePayment(Request $request): void
    {
        $this->requireStrings($request, [
            'partnerServiceId',
            'customerNo',
            'virtualAccountNo',
            'paymentRequestId',
        ], '25');

        if (! $request->has('paidAmount')) {
            throw SnapValidationException::missing('paidAmount', '25');
        }

        $this->validateAmount($request->input('paidAmount'), 'paidAmount', '25');

        if ($request->filled('inquiryRequestId')
            && $request->input('paymentRequestId') !== $request->input('inquiryRequestId')) {
            throw SnapValidationException::invalidFormat('paymentRequestId', '25');
        }

        foreach (['trxId', 'trxDateTime', 'hashedSourceAccountNo'] as $field) {
            if ($request->has($field) && ! is_string($request->input($field))) {
                throw SnapValidationException::invalidFormat($field, '25');
            }
        }

        $additionalInfo = $request->input('additionalInfo');
        if ($additionalInfo !== null && ! is_array($additionalInfo)) {
            throw SnapValidationException::invalidFormat('additionalInfo', '25');
        }

        if (is_array($additionalInfo)
            && array_key_exists('hashedSourceAccountName', $additionalInfo)
            && ! is_string($additionalInfo['hashedSourceAccountName'])) {
            throw SnapValidationException::invalidFormat('additionalInfo.hashedSourceAccountName', '25');
        }
    }

    private function validateAmount(mixed $amount, string $field, string $serviceCode): void
    {
        if (! is_array($amount)) {
            throw SnapValidationException::invalidFormat($field, $serviceCode);
        }

        foreach (['value', 'currency'] as $nestedField) {
            if (! array_key_exists($nestedField, $amount) || $amount[$nestedField] === null || $amount[$nestedField] === '') {
                throw SnapValidationException::missing("{$field}.{$nestedField}", $serviceCode);
            }
        }

        if (! is_string($amount['value']) || ! preg_match('/^\d+(?:\.\d{2})$/', $amount['value'])) {
            throw SnapValidationException::invalidFormat("{$field}.value", $serviceCode);
        }

        if (! is_string($amount['currency']) || $amount['currency'] !== 'IDR') {
            throw SnapValidationException::invalidFormat("{$field}.currency", $serviceCode);
        }
    }

    private function requireStrings(Request $request, array $fields, string $serviceCode): void
    {
        foreach ($fields as $field) {
            if (! $request->has($field) || $request->input($field) === '') {
                throw SnapValidationException::missing($field, $serviceCode);
            }

            if (! is_string($request->input($field))) {
                throw SnapValidationException::invalidFormat($field, $serviceCode);
            }
        }
    }
}

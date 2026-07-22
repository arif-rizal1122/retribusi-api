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
        ], '24');
    }

    public function validatePayment(Request $request): void
    {
        $this->requireStrings($request, [
            'partnerServiceId',
            'customerNo',
            'virtualAccountNo',
        ], '25');

        if (! $request->has('paidAmount')) {
            throw SnapValidationException::missing('paidAmount', '25');
        }

        $paidAmount = $request->input('paidAmount');
        if (! is_array($paidAmount)) {
            throw SnapValidationException::invalidFormat('paidAmount', '25');
        }

        foreach (['value', 'currency'] as $field) {
            if (! array_key_exists($field, $paidAmount) || $paidAmount[$field] === null || $paidAmount[$field] === '') {
                throw SnapValidationException::missing("paidAmount.{$field}", '25');
            }
        }

        if (! is_numeric($paidAmount['value'])) {
            throw SnapValidationException::invalidFormat('paidAmount.value', '25');
        }

        if (! is_string($paidAmount['currency']) || $paidAmount['currency'] !== 'IDR') {
            throw SnapValidationException::invalidFormat('paidAmount.currency', '25');
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

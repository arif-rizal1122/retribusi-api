<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrationVerificationService
{
    public function __construct(
        private BillCreationService $billCreationService
    ) {
    }

    public function updateStatus(Verification $verification, string $status, ?string $notes, User $user): Verification
    {
        return DB::transaction(function () use ($verification, $status, $notes, $user) {
            $verification->update([
                'status' => $status,
                'notes' => $notes,
                'verifier_id' => $user->id,
                'verified_at' => in_array($status, ['approved', 'rejected']) ? Carbon::now() : null,
            ]);

            if ($verification->tax_object_id && $status === 'approved') {
                $this->approveTaxObject($verification, $user);
            }

            if ($verification->tax_object_id && $status === 'rejected') {
                TaxObject::withoutGlobalScopes()
                    ->whereKey($verification->tax_object_id)
                    ->update(['status' => 'rejected']);
            }

            return $verification->fresh(['opd', 'submitter', 'verifier', 'taxObject.classification']);
        });
    }

    private function approveTaxObject(Verification $verification, User $user): void
    {
        $taxObject = TaxObject::withoutGlobalScopes()
            ->with(['retributionType', 'classification', 'taxpayer'])
            ->find($verification->tax_object_id);

        if (!$taxObject) {
            return;
        }

        $taxObject->update([
            'status' => 'active',
            'approved_at' => Carbon::now(),
            'approved_by' => $user->id,
        ]);

        $this->billCreationService->createForTaxObject(
            $taxObject,
            $user,
            null,
            $taxObject->metadata ?? [],
            [
                'source' => 'verification_approval',
                'verification_id' => $verification->id,
            ],
            null,
            'verification_approval',
            null,
            true
        );
    }
}

<?php

namespace App\Services;

use App\Models\TaxObject;
use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
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

            return $verification->fresh(['opd', 'submitter', 'verifier', 'taxObject.classification', 'taxObject.taxpayer']);
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

        $this->assertTaxObjectCanBeApproved($taxObject);

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

    private function assertTaxObjectCanBeApproved(TaxObject $taxObject): void
    {
        $errors = [];
        $taxpayer = $taxObject->taxpayer;
        $metadata = $taxObject->metadata ?? [];

        if (!$taxpayer?->name) {
            $errors['taxpayer.name'] = 'Nama wajib pajak wajib diisi sebelum objek disetujui.';
        }

        if (!$taxObject->name) {
            $errors['tax_object.name'] = 'Nama objek/unit wajib diisi sebelum objek disetujui.';
        }

        if (!$taxObject->address) {
            $errors['tax_object.address'] = 'Alamat objek wajib diisi sebelum objek disetujui.';
        }

        $district = $taxObject->district ?: $taxpayer?->district;
        $subDistrict = $taxObject->sub_district ?: $taxpayer?->sub_district;

        if (!$district) {
            $errors['tax_object.district'] = 'Kecamatan objek wajib diisi sebelum objek disetujui.';
        }

        if (!$subDistrict) {
            $errors['tax_object.sub_district'] = 'Kelurahan objek wajib diisi sebelum objek disetujui.';
        }

        if ($taxObject->classification) {
            foreach (($taxObject->classification->form_schema ?? []) as $field) {
                if (!($field['required'] ?? false)) {
                    continue;
                }

                $key = $field['key'] ?? null;
                if (!$key) {
                    continue;
                }

                $value = $metadata[$key] ?? null;
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $label = $field['label'] ?? $key;
                    $errors["metadata.{$key}"] = "{$label} wajib diisi sebelum objek disetujui.";
                }
            }

            foreach (($taxObject->classification->requirements ?? []) as $requirement) {
                if (!($requirement['required'] ?? false)) {
                    continue;
                }

                $key = $requirement['key'] ?? null;
                if (!$key) {
                    continue;
                }

                if (empty($metadata[$key])) {
                    $label = $requirement['label'] ?? $requirement['name'] ?? $key;
                    $errors[$key] = "{$label} wajib diunggah sebelum objek disetujui.";
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}

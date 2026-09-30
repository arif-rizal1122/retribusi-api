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

            // ═══ STATE GUARD: Pagar Validasi Alur Verifikasi ═══
            // Jika admin mencoba approve, periksa apakah layanan ini wajib survei lapangan
            if ($status === 'approved' && $verification->tax_object_id) {
                $this->assertFieldSurveyCompleted($verification);
            }

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

            $updated = $verification->fresh(['opd', 'submitter', 'verifier', 'taxObject.classification', 'taxObject.taxpayer']);

            $this->notifyTaxpayerVerificationResult($updated, $status, $notes);

            // ═══ AUDIT TRAIL: Pencatatan Jejak Aksi ═══
            \Illuminate\Support\Facades\Log::info('Verification status updated', [
                'verification_id' => $verification->id,
                'tax_object_id' => $verification->tax_object_id,
                'old_status' => $verification->getOriginal('status'),
                'new_status' => $status,
                'admin_id' => $user->id,
                'admin_name' => $user->name,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);

            return $updated;
        });
    }

    /**
     * ═══ STATE GUARD: Pagar Validasi Survei Lapangan ═══
     *
     * Memastikan bahwa permohonan dengan jenis layanan "Wajib Survei Lapangan"
     * TIDAK BISA disetujui jika:
     * 1. Belum ada penugasan lapangan aktif (PetugasTask)
     * 2. Penugasan belum berstatus 'completed'
     * 3. Laporan lapangan belum memuat bukti foto dan koordinat
     */
    private function assertFieldSurveyCompleted(Verification $verification): void
    {
        $taxObject = TaxObject::withoutGlobalScopes()
            ->with('classification')
            ->find($verification->tax_object_id);

        if (!$taxObject || !$taxObject->classification) {
            return; // Tidak ada klasifikasi = tidak ada aturan khusus
        }

        $classification = $taxObject->classification;

        // Jika layanan TIDAK wajib survei lapangan, izinkan langsung
        if (!$classification->requiresFieldSurvey()) {
            return;
        }

        // Cari penugasan field_survey untuk tax_object ini
        $completedSurvey = \App\Models\PetugasTask::where('tax_object_id', $taxObject->id)
            ->where('task_type', 'field_survey')
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $errors = [];

        // 1. Belum ada penugasan sama sekali atau belum selesai
        $anyTask = \App\Models\PetugasTask::where('tax_object_id', $taxObject->id)
            ->where('task_type', 'field_survey')
            ->exists();

        if (!$anyTask) {
            $errors['field_survey'] = 'Layanan ini wajib survei lapangan. Silakan tugaskan petugas lapangan terlebih dahulu sebelum menyetujui.';
        } elseif (!$completedSurvey) {
            $errors['field_survey'] = 'Penugasan survei lapangan belum selesai. Tunggu petugas menyelesaikan dan mengunggah laporan lapangan.';
        } else {
            // 2. Periksa kelengkapan bukti lapangan
            if (empty($completedSurvey->completion_photo_path)) {
                $errors['completion_photo'] = 'Laporan lapangan belum memuat foto bukti fisik dari petugas.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function notifyTaxpayerVerificationResult(Verification $verification, string $status, ?string $notes): void
    {
        try {
            $taxObject = $verification->taxObject;
            $taxpayer = $verification->taxpayer ?: $taxObject?->taxpayer;
            $phone = $taxpayer?->phone;

            if (empty($phone) && $taxObject && !empty($taxObject->metadata)) {
                $phone = $taxObject->metadata['phone']
                    ?? $taxObject->metadata['no_hp']
                    ?? $taxObject->metadata['telepon']
                    ?? $taxObject->metadata['kontak_pemohon']
                    ?? $taxObject->metadata['kontak_wp']
                    ?? null;
            }

            if (empty($phone)) {
                return;
            }

            $waGateway = app(WaGatewayService::class);
            $taxpayerName = $taxpayer?->name ?: $verification->taxpayer_name ?: 'Wajib Pajak';
            $docNumber = $verification->document_number;
            $objName = $taxObject?->name ?: 'Objek Pajak / Retribusi';
            $serviceName = $taxObject?->classification?->name ?: $taxObject?->retributionType?->name ?: 'Layanan M-PAD';

            if ($status === 'approved') {
                $waMessage = "🏛️ *PEMERINTAH KOTA BAUBAU*\n"
                           . "*Badan Pendapatan Daerah (BAPENDA)*\n"
                           . "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                           . "Halo, *{$taxpayerName}*!\n\n"
                           . "Kabar baik! Permohonan pendaftaran unit/objek baru Anda telah *DISETUJUI* oleh Tim Verifikator:\n"
                           . "📋 *Layanan*: {$serviceName}\n"
                           . "🏷️ *Nama Objek*: {$objName}\n"
                           . "🔢 *No. Registrasi*: `{$docNumber}`\n"
                           . "✅ *Status*: *DISETUJUI / AKTIF*\n\n"
                           . "Unit/objek Anda kini telah resmi terdaftar dan aktif di sistem M-PAD Kota Baubau.\n"
                           . "Anda dapat melihat rincian unit dan melakukan pelaporan SPTPD/pembayaran melalui aplikasi *M-PAD Baubau*.\n\n"
                           . "_Pesan otomatis resmi Sistem M-PAD Pemerintah Kota Baubau_";
            } elseif ($status === 'rejected') {
                $catatan = $notes ? ":\n_\"{$notes}\"_" : ". Silakan periksa kelengkapan dokumen pendukung.";
                $waMessage = "🏛️ *PEMERINTAH KOTA BAUBAU*\n"
                           . "*Badan Pendapatan Daerah (BAPENDA)*\n"
                           . "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                           . "Halo, *{$taxpayerName}*!\n\n"
                           . "Pemberitahuan terkait permohonan pendaftaran unit/objek Anda:\n"
                           . "📋 *Layanan*: {$serviceName}\n"
                           . "🏷️ *Nama Objek*: {$objName}\n"
                           . "🔢 *No. Registrasi*: `{$docNumber}`\n"
                           . "⚠️ *Status*: *PERLU PERBAIKAN / DITOLAK*\n\n"
                           . "Catatan verifikator{$catatan}\n\n"
                           . "Silakan perbaiki data atau hubungi petugas Bapenda melalui aplikasi *M-PAD Baubau*.\n\n"
                           . "_Pesan otomatis resmi Sistem M-PAD Pemerintah Kota Baubau_";
            } else {
                return;
            }

            $waGateway->sendMessage($phone, $waMessage);

            if (\Illuminate\Support\Facades\Schema::hasTable('notification_logs')) {
                \Illuminate\Support\Facades\DB::table('notification_logs')->insert([
                    'taxpayer_id' => $taxpayer?->id,
                    'phone' => $phone,
                    'channel' => 'whatsapp',
                    'type' => 'unit_verification_'.$status,
                    'reference_type' => 'verifications',
                    'reference_id' => $verification->id,
                    'message_preview' => "Verifikasi {$docNumber} ({$objName}) {$status}.",
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp verification result notification failed: '.$e->getMessage());
        }
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

        // Terbitkan tagihan otomatis HANYA jika kalkulasi menghasilkan nominal > 0.
        // Objek pajak self-assessment (seperti Walet/PBJT) yang belum memiliki laporan panen/omzet
        // tidak boleh diterbitkan tagihan Rp 0, melainkan menunggu pelaporan SPTPD dari wajib pajak.
        try {
            $calcService = app(TaxCalculationService::class);
            $estimatedAmount = (float) $calcService->calculateAmount($taxObject);

            if ($estimatedAmount > 0) {
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Automatic bill creation on approval skipped: '.$e->getMessage());
        }
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

<?php

namespace App\Services;

use App\Models\AssetRental;
use App\Models\Bill;
use App\Models\RusunRoom;
use App\Models\Taxpayer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RusunBillingService
{
    /**
     * Validasi pembayaran FIFO (First-In, First-Out) untuk tagihan Rusunawa.
     * Wajib pajak tidak boleh membayar bulan berjalan jika masih ada tunggakan bulan sebelumnya.
     *
     * @param  Taxpayer  $taxpayer
     * @param  Collection|array  $selectedBillIds
     * @throws ValidationException
     */
    public function validateFifoPayment(Taxpayer $taxpayer, Collection|array $selectedBillIds): void
    {
        $selectedIds = collect($selectedBillIds)->map(fn ($id) => (int) $id)->all();

        // Ambil semua tagihan pending/overdue Disperkim milik wajib pajak, urutkan periode terlama dulu
        $unpaidRusunBills = Bill::where('taxpayer_id', $taxpayer->id)
            ->where('opd_id', 11)
            ->whereIn('status', ['pending', 'overdue', 'unpaid'])
            ->orderBy('period_start', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($unpaidRusunBills->isEmpty()) {
            return;
        }

        // Cari tagihan tertua yang belum lunas
        $firstUnpaid = $unpaidRusunBills->first();

        // Jika tagihan tertua tidak termasuk dalam yang dibayar saat ini, tolak
        if (!in_array($firstUnpaid->id, $selectedIds, true)) {
            $periodLabel = $firstUnpaid->period ?? ($firstUnpaid->period_start?->format('Y-m') ?? 'sebelumnya');
            throw ValidationException::withMessages([
                'bill_ids' => "Harap lunasi tagihan bulan sebelumnya terlebih dahulu (FIFO). Tagihan periode {$periodLabel} belum lunas.",
            ]);
        }

        // Pastikan tidak ada celah/gap di antara tagihan-tagihan yang dipilih
        // Misalnya: tagihan bulan 1 dan 3 dipilih, tapi bulan 2 dilewati
        $selectedUnpaidIndexMap = [];
        foreach ($unpaidRusunBills as $idx => $bill) {
            if (in_array($bill->id, $selectedIds, true)) {
                $selectedUnpaidIndexMap[] = $idx;
            }
        }

        for ($i = 0; $i < count($selectedUnpaidIndexMap); $i++) {
            if ($selectedUnpaidIndexMap[$i] !== $i) {
                $skippedBill = $unpaidRusunBills[$i];
                $periodLabel = $skippedBill->period ?? ($skippedBill->period_start?->format('Y-m') ?? 'sebelumnya');
                throw ValidationException::withMessages([
                    'bill_ids' => "Harap lunasi tagihan bulan sebelumnya terlebih dahulu (FIFO). Tagihan periode {$periodLabel} terlewati.",
                ]);
            }
        }
    }

    /**
     * Generate tagihan bulanan SKRD untuk seluruh kamar rusun aktif (occupied).
     *
     * @param  string  $month  Format 'YYYY-MM'
     * @return int Jumlah tagihan yang berhasil di-generate
     */
    public function generateMonthlyBills(string $month): int
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $dueDate = $startDate->copy()->day(20)->endOfDay(); // Jatuh tempo tanggal 20

        // Ambil semua rental aktif rusunawa
        $activeRentals = AssetRental::with(['room', 'assetItem', 'taxpayer'])
            ->where('status', 'Disetujui')
            ->whereNotNull('room_id')
            ->whereHas('room', fn ($q) => $q->where('status', 'occupied'))
            ->get();

        $generatedCount = 0;

        foreach ($activeRentals as $rental) {
            // Cek apakah tagihan untuk periode ini sudah pernah dibuat
            $exists = Bill::where('taxpayer_id', $rental->taxpayer_id)
                ->where('opd_id', 11)
                ->where('period', $month)
                ->where(function ($q) use ($rental) {
                    $q->whereJsonContains('metadata->rental_id', $rental->id)
                        ->orWhereJsonContains('metadata->room_id', $rental->room_id);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            $billNumber = 'SKRD-RSN-' . str_replace('-', '', $month) . '-' . str_pad((string) $rental->id, 4, '0', STR_PAD_LEFT);
            // Hindari duplikasi nomor bill jika ada
            if (Bill::where('bill_number', $billNumber)->exists()) {
                $billNumber .= '-' . strtoupper(bin2hex(random_bytes(2)));
            }

            $amount = (float) ($rental->tarif_per_satuan ?: ($rental->assetItem?->tarif_default ?: 0));

            Bill::create([
                'taxpayer_id' => $rental->taxpayer_id,
                'opd_id' => 11,
                'retribution_type_id' => $rental->retribution_type_id ?? 106,
                'retribution_classification_id' => $rental->retribution_classification_id ?? 211,
                'bill_number' => $billNumber,
                'amount' => $amount,
                'status' => 'pending',
                'period' => $month,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'due_date' => $dueDate,
                'metadata' => [
                    'rental_id' => $rental->id,
                    'rental_code' => $rental->rental_code,
                    'room_id' => $rental->room_id,
                    'room_number' => $rental->room?->room_number,
                    'complex_code' => $rental->room?->complex_code,
                    'floor' => $rental->room?->floor,
                    'periode_bulan' => $month,
                    'source' => 'rusun_monthly_generator',
                ],
            ]);

            $generatedCount++;
        }

        return $generatedCount;
    }

    /**
     * Check-in penghuni ke kamar: Kunci kamar (occupied) dan setujui rental.
     *
     * @param  AssetRental  $rental
     * @param  RusunRoom  $room
     * @param  User|null  $admin
     * @throws ValidationException
     */
    public function checkInRoom(AssetRental $rental, RusunRoom $room, ?User $admin = null): void
    {
        DB::transaction(function () use ($rental, $room, $admin) {
            $lockedRoom = RusunRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();

            if ($lockedRoom->status !== 'available') {
                throw ValidationException::withMessages([
                    'room_id' => "Kamar {$lockedRoom->room_number} tidak tersedia saat ini (status: {$lockedRoom->status}).",
                ]);
            }

            $lockedRoom->update([
                'status' => 'occupied',
                'current_rental_id' => $rental->id,
            ]);

            $rental->update([
                'room_id' => $lockedRoom->id,
                'status' => 'Disetujui',
                'tanggal_approval' => now(),
                'approved_by_user_id' => $admin?->id,
            ]);
        });
    }

    /**
     * Check-out penghuni: Ditolak jika ada tunggakan. Bebaskan kamar jika lunas.
     *
     * @param  AssetRental  $rental
     * @param  User|null  $admin
     * @throws ValidationException
     */
    public function checkOutRoom(AssetRental $rental, ?User $admin = null): void
    {
        // Validasi tunggakan: Periksa apakah masih ada tagihan Disperkim yang belum lunas
        $hasUnpaidBills = Bill::where('taxpayer_id', $rental->taxpayer_id)
            ->where('opd_id', 11)
            ->whereIn('status', ['pending', 'overdue', 'unpaid'])
            ->exists();

        if ($hasUnpaidBills) {
            throw ValidationException::withMessages([
                'checkout' => 'Check-out ditolak: Penghuni masih memiliki tagihan tunggakan rusunawa yang belum lunas.',
            ]);
        }

        DB::transaction(function () use ($rental) {
            if ($rental->room_id) {
                $room = RusunRoom::where('id', $rental->room_id)->lockForUpdate()->first();
                if ($room) {
                    $room->update([
                        'status' => 'available',
                        'current_rental_id' => null,
                    ]);
                }
            }

            $rental->update([
                'status' => 'Selesai',
                'tanggal_pengembalian' => now(),
            ]);
        });
    }
}

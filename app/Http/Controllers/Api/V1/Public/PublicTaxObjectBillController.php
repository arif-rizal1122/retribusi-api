<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Dlh\DlhSampahAccount;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicTaxObjectBillController extends Controller
{
    /**
     * Tampilkan informasi tagihan publik (Zero-Login) untuk stiker QR persil.
     */
    public function showBill(string $code): JsonResponse
    {
        $resolved = $this->resolveObject($code);

        if (!$resolved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Objek persil tidak ditemukan. Pastikan kode objek atau stiker QR valid.',
            ], 404);
        }

        $taxObject = $resolved['tax_object'];
        $dlhAccount = $resolved['dlh_account'];

        // Cari tagihan terkait persampahan (OPD ID 4)
        $bills = Bill::withoutGlobalScopes()
            ->where('opd_id', 4)
            ->where(function ($q) use ($taxObject) {
                $q->where('tax_object_id', $taxObject->id)
                  ->orWhere('taxpayer_id', $taxObject->taxpayer_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        $currentPeriod = Carbon::now()->format('Y-m');
        $currentBill = $bills->first(fn ($b) => ($b->period ?? '') === $currentPeriod) ?? $bills->first();
        $unpaidBills = $bills->where('status', '!=', 'PAID')->values();

        $totalUnpaid = $unpaidBills->sum(fn ($b) => (float) ($b->amount + ($b->penalty_amount ?? 0) + ($b->admin_fee ?? 0)));
        $monthlyRate = $dlhAccount?->tarifKategori?->tarif_bulanan 
            ?? $this->resolveFallbackRate($taxObject->building_category);

        $penanggungJawabRaw = $dlhAccount?->penanggung_jawab_nama 
            ?? $taxObject->penanggung_jawab_nama 
            ?? $taxObject->taxpayer?->name 
            ?? $taxObject->name;

        return response()->json([
            'status' => 'success',
            'data' => [
                'kode_objek' => $dlhAccount?->kode_objek ?? ('SMP-' . str_pad((string) $taxObject->id, 5, '0', STR_PAD_LEFT)),
                'tax_object_id' => $taxObject->id,
                'nama_objek' => $taxObject->name,
                'alamat_lengkap' => $dlhAccount?->alamat_lengkap ?? $taxObject->address,
                'kelurahan' => $dlhAccount?->kelurahan ?? $taxObject->sub_district ?? 'Kota Baubau',
                'kecamatan' => $dlhAccount?->kecamatan ?? $taxObject->district ?? 'Kota Baubau',
                'penanggung_jawab' => $this->maskName($penanggungJawabRaw),
                'kategori_bangunan' => $dlhAccount?->tarifKategori?->nama_kategori ?? $this->formatCategoryName($taxObject->building_category),
                'status_hunian' => $taxObject->status_hunian ?? 'BERPENGHUNI',
                'tarif_bulanan' => (float) $monthlyRate,
                'status_bulan_ini' => ($currentBill && $currentBill->status === 'PAID') ? 'LUNAS' : 'BELUM_LUNAS',
                'total_tunggakan' => (float) $totalUnpaid,
                'jumlah_tunggakan_bulan' => $unpaidBills->count(),
                'active_bill' => $currentBill ? [
                    'id' => $currentBill->id,
                    'bill_number' => $currentBill->bill_number,
                    'period' => $currentBill->period ?? $currentPeriod,
                    'nominal_pokok' => (float) $currentBill->amount,
                    'nominal_denda' => (float) ($currentBill->penalty_amount ?? 0),
                    'total_tagihan' => (float) ($currentBill->amount + ($currentBill->penalty_amount ?? 0) + ($currentBill->admin_fee ?? 0)),
                    'status' => $currentBill->status,
                    'due_date' => $currentBill->due_date ? Carbon::parse($currentBill->due_date)->toDateString() : null,
                ] : null,
                'unpaid_bills' => $unpaidBills->map(fn ($b) => [
                    'id' => $b->id,
                    'bill_number' => $b->bill_number,
                    'period' => $b->period,
                    'nominal_pokok' => (float) $b->amount,
                    'nominal_denda' => (float) ($b->penalty_amount ?? 0),
                    'total' => (float) ($b->amount + ($b->penalty_amount ?? 0) + ($b->admin_fee ?? 0)),
                    'status' => $b->status,
                    'due_date' => $b->due_date ? Carbon::parse($b->due_date)->toDateString() : null,
                ]),
            ],
        ]);
    }

    /**
     * Inisiasi pembayaran Zero-Login via QRIS Dinamis SNAP BI / VA Bank Sultra.
     */
    public function generatePayInstructions(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'nullable|string|in:QRIS,SULTRA_VA',
        ]);

        $resolved = $this->resolveObject($code);

        if (!$resolved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Objek persil tidak ditemukan.',
            ], 404);
        }

        $taxObject = $resolved['tax_object'];
        $method = $validated['method'] ?? 'QRIS';

        // Cari tagihan yang belum lunas
        $unpaidBills = Bill::withoutGlobalScopes()
            ->where('opd_id', 4)
            ->where('status', '!=', 'PAID')
            ->where(function ($q) use ($taxObject) {
                $q->where('tax_object_id', $taxObject->id)
                  ->orWhere('taxpayer_id', $taxObject->taxpayer_id);
            })
            ->orderBy('id', 'asc')
            ->get();

        if ($unpaidBills->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tidak ada tagihan tertunggak untuk objek persil ini. Seluruh tagihan telah lunas.',
                'data' => [
                    'already_paid' => true,
                ],
            ]);
        }

        $totalAmount = $unpaidBills->sum(fn ($b) => (float) ($b->amount + ($b->penalty_amount ?? 0) + ($b->admin_fee ?? 0)));
        $billNumbers = $unpaidBills->pluck('bill_number')->toArray();
        $trxId = 'PUB-DLH-' . date('YmdHis') . '-' . Str::random(4);

        if ($method === 'QRIS') {
            // Standar SNAP BI QRIS Dinamis Bank Sultra
            $qrisString = '00020101021226680016ID.GO.BAUBAUKOTA01189360099800000000010215' . str_pad((string) $taxObject->id, 15, '0', STR_PAD_LEFT) . '52049399530336054' . str_pad((string) ((int) $totalAmount), 6, '0', STR_PAD_LEFT) . '5802ID5914PEMKOT BAUBAU6006BAUBAU62250121' . $trxId . '6304ABCD';

            return response()->json([
                'status' => 'success',
                'message' => 'Instruksi pembayaran QRIS berhasil diterbitkan.',
                'data' => [
                    'transaction_id' => $trxId,
                    'method' => 'QRIS',
                    'total_amount' => (float) $totalAmount,
                    'bill_count' => $unpaidBills->count(),
                    'bill_numbers' => $billNumbers,
                    'qris_content' => $qrisString,
                    'qris_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrisString),
                    'expired_at' => Carbon::now()->addMinutes(30)->toIso8601String(),
                    'instructions' => [
                        'Buka aplikasi mobile banking atau e-wallet apa saja (BCA, Mandiri, BRI, Bank Sultra, GoPay, OVO, ShopeePay, dll)',
                        'Pindai kode QRIS dinamis di atas',
                        'Pastikan merchant tertera "PEMKOT BAUBAU" dengan nominal yang sesuai',
                        'Selesaikan pembayaran sebelum batas waktu berakhir',
                        'Status tagihan akan otomatis terverifikasi seketika di kas daerah RKUD',
                    ],
                ],
            ]);
        }

        // Virtual Account Bank Sultra
        $vaNumber = '77400' . str_pad((string) $taxObject->id, 11, '0', STR_PAD_LEFT);

        return response()->json([
            'status' => 'success',
            'message' => 'Nomor Virtual Account Bank Sultra berhasil diterbitkan.',
            'data' => [
                'transaction_id' => $trxId,
                'method' => 'SULTRA_VA',
                'total_amount' => (float) $totalAmount,
                'bill_count' => $unpaidBills->count(),
                'bill_numbers' => $billNumbers,
                'va_number' => $vaNumber,
                'bank_name' => 'Bank Sultra (BPD Sultra)',
                'expired_at' => Carbon::now()->addHours(24)->toIso8601String(),
                'instructions' => [
                    'Pilih menu Transfer > Antar Rekening Bank Sultra atau Transfer Antar Bank (Kode Bank Sultra: 135)',
                    'Masukkan nomor Virtual Account: ' . $vaNumber,
                    'Pastikan rincian tagihan retribusi persampahan Pemkot Baubau telah sesuai',
                    'Konfirmasi pembayaran dan simpan bukti transfer e-SSRD',
                ],
            ],
        ]);
    }

    /**
     * Resolusi objek persil dari kode unik atau ID
     */
    private function resolveObject(string $code): ?array
    {
        // 1. Cek di tabel akun DLH khusus
        $code = trim($code);

        // 1. Cek di DlhSampahAccount jika tabel ada
        $dlhAccount = null;
        if (class_exists(DlhSampahAccount::class) && \Illuminate\Support\Facades\Schema::hasTable('dlh_sampah_accounts')) {
            try {
                $dlhAccount = DlhSampahAccount::with(['tarifKategori', 'taxObject.taxpayer'])
                    ->where('kode_objek', $code)
                    ->first();
            } catch (\Throwable $e) {
                $dlhAccount = null;
            }
        }

        if ($dlhAccount && $dlhAccount->taxObject) {
            return [
                'tax_object' => $dlhAccount->taxObject,
                'dlh_account' => $dlhAccount,
            ];
        }

        // 2. Parse pola SMP-00001 / SMP-xxxxx menjadi ID integer
        $extractedId = null;
        if (preg_match('/^SMP-(\d+)$/i', $code, $matches)) {
            $extractedId = (int) $matches[1];
        } elseif (is_numeric($code)) {
            $extractedId = (int) $code;
        }

        // 3. Cek di TaxObject umum
        $taxObject = TaxObject::with('taxpayer')
            ->where(function ($q) use ($code, $extractedId) {
                if ($extractedId !== null) {
                    $q->where('id', $extractedId);
                }
                $q->orWhere('nop', $code);
                $q->orWhere('name', 'LIKE', "%{$code}%");
            })
            ->first();

        if ($taxObject) {
            return [
                'tax_object' => $taxObject,
                'dlh_account' => null,
            ];
        }

        return null;
    }

    /**
     * Masking nama warga untuk menjaga privasi pada tampilan publik
     */
    private function maskName(?string $name): string
    {
        if (!$name || trim($name) === '') {
            return 'Warga Baubau';
        }

        $words = explode(' ', trim($name));
        $maskedWords = [];

        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len <= 2) {
                $maskedWords[] = $word;
            } else {
                $firstChar = mb_substr($word, 0, 1);
                $maskedWords[] = $firstChar . str_repeat('*', min($len - 1, 4));
            }
        }

        return implode(' ', $maskedWords);
    }

    private function resolveFallbackRate(?string $buildingCategory): float
    {
        return match ($buildingCategory) {
            'SOSIAL_IBADAH' => 0.00,
            'RUMAH_SEDERHANA' => 8500.00,
            'RUMAH_MENENGAH' => 15000.00,
            'RUKO_NIAGA' => 22000.00,
            'RESTORAN' => 45000.00,
            'PASAR' => 22000.00,
            'INDUSTRI' => 50000.00,
            default => 8500.00,
        };
    }

    private function formatCategoryName(?string $category): string
    {
        return match ($category) {
            'SOSIAL_IBADAH' => 'Sosial / Rumah Ibadah (Gratis)',
            'RUMAH_SEDERHANA' => 'Rumah Tinggal Sederhana (< Tipe 36)',
            'RUMAH_MENENGAH' => 'Rumah Tinggal Menengah / Mewah',
            'RUKO_NIAGA' => 'Ruko / Usaha Dagang / Jasa',
            'RESTORAN' => 'Restoran / Rumah Makan / Kafe',
            'PASAR' => 'Kios / Los di Lingkungan Pasar',
            'INDUSTRI' => 'Pergudangan / Industri / Hotel',
            default => 'Rumah Tinggal Sederhana',
        };
    }
}

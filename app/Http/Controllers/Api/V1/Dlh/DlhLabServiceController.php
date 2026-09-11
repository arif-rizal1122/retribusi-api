<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DlhLabServiceController extends Controller
{
    /**
     * Ambil daftar 7 parameter laboratorium lingkungan aktif
     */
    public function indexParameters()
    {
        $parameters = DB::table('dlh_lab_parameters')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $parameters,
        ]);
    }

    /**
     * Buat pemesanan/order pengujian sampel laboratorium baru
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'applicant_name' => 'required|string|max:255',
            'institution_name' => 'nullable|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'applicant_address' => 'nullable|string',
            'sample_matrix' => 'required|string|in:AIR_LIMBAH,AIR_SUNGAI,AIR_BERSIH,AIR_LAUT',
            'sampling_location' => 'nullable|string|max:255',
            'sampling_date' => 'required|date',
            'parameter_ids' => 'required|array|min:1',
            'parameter_ids.*' => 'integer|exists:dlh_lab_parameters,id',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $parameters = DB::table('dlh_lab_parameters')
                ->whereIn('id', $validated['parameter_ids'])
                ->get();

            $totalAmount = $parameters->sum('tariff');
            $orderNumber = 'LAB-DLH-' . date('ymd') . '-' . strtoupper(Str::random(5));

            $orderId = DB::table('dlh_lab_orders')->insertGetId([
                'order_number' => $orderNumber,
                'taxpayer_id' => $request->user()?->taxpayer_id ?? null,
                'applicant_name' => $validated['applicant_name'],
                'institution_name' => $validated['institution_name'] ?? null,
                'contact_phone' => $validated['contact_phone'],
                'applicant_address' => $validated['applicant_address'] ?? null,
                'sample_matrix' => $validated['sample_matrix'],
                'sampling_location' => $validated['sampling_location'] ?? null,
                'sampling_date' => $validated['sampling_date'],
                'total_amount' => $totalAmount,
                'status' => 'DRAFT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Simpan pivot parameter order
            $pivotData = [];
            foreach ($parameters as $param) {
                $pivotData[] = [
                    'lab_order_id' => $orderId,
                    'parameter_id' => $param->id,
                    'tariff_snapshot' => $param->tariff,
                    'quality_standard_value' => $param->standard_reference,
                    'compliance_status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('dlh_lab_order_parameters')->insert($pivotData);

            $order = DB::table('dlh_lab_orders')->where('id', $orderId)->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan uji laboratorium berhasil didaftarkan.',
                'data' => [
                    'order' => $order,
                    'parameters' => $parameters,
                ],
            ], 201);
        });
    }

    /**
     * Terbitkan tagihan (Billing/SKRD) untuk permohonan laboratorium
     */
    public function generateBilling($orderId)
    {
        $order = DB::table('dlh_lab_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        if ($order->bill_id) {
            $existingBill = Bill::find($order->bill_id);
            return response()->json([
                'status' => 'success',
                'message' => 'Tagihan sudah pernah diterbitkan.',
                'data' => $existingBill,
            ]);
        }

        return DB::transaction(function () use ($order) {
            $taxpayerId = $order->taxpayer_id;
            if (!$taxpayerId) {
                $taxpayer = \App\Models\Taxpayer::withoutGlobalScopes()->firstOrCreate(
                    [
                        'opd_id' => 4,
                        'name' => $order->applicant_name,
                    ],
                    [
                        'phone' => $order->contact_phone,
                        'address' => $order->applicant_address ?? 'Kota Baubau',
                        'is_active' => true,
                    ]
                );
                $taxpayerId = $taxpayer->id;
                DB::table('dlh_lab_orders')->where('id', $order->id)->update(['taxpayer_id' => $taxpayerId]);
            }

            $billNumber = 'SKRD-LAB-' . date('ymd') . '-' . strtoupper(Str::random(5));
            $dueDate = Carbon::now()->addDays(7)->toDateString();

            $bill = Bill::create([
                'taxpayer_id' => $taxpayerId,
                'opd_id' => 4, // DLH
                'retribution_type_id' => 17, // Retribusi
                'bill_number' => $billNumber,
                'amount' => $order->total_amount,
                'admin_fee' => 0.00,
                'penalty_amount' => 0.00,
                'status' => 'UNPAID',
                'period' => date('Y-m'),
                'period_start' => Carbon::now()->startOfMonth()->toDateString(),
                'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                'due_date' => $dueDate,
                'metadata' => json_encode([
                    'type' => 'LAB_ENVIRONMENTAL',
                    'order_number' => $order->order_number,
                    'applicant_name' => $order->applicant_name,
                    'institution' => $order->institution_name,
                    'matrix' => $order->sample_matrix,
                ]),
            ]);

            DB::table('dlh_lab_orders')->where('id', $order->id)->update([
                'bill_id' => $bill->id,
                'status' => 'BILLING_ISSUED',
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tagihan SKRD Laboratorium berhasil diterbitkan.',
                'data' => $bill,
            ]);
        });
    }

    /**
     * Analis laboratorium menginput nilai hasil pengujian dan kesesuaian baku mutu
     */
    public function uploadResult(Request $request, $orderId)
    {
        $validated = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.parameter_id' => 'required|integer',
            'results.*.result_value' => 'required|string',
            'results.*.compliance_status' => 'required|string|in:COMPLIANT,EXCEEDED',
            'lhu_document_url' => 'nullable|string',
        ]);

        $order = DB::table('dlh_lab_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        return DB::transaction(function () use ($order, $validated) {
            foreach ($validated['results'] as $item) {
                DB::table('dlh_lab_order_parameters')
                    ->where('lab_order_id', $order->id)
                    ->where('parameter_id', $item['parameter_id'])
                    ->update([
                        'result_value' => $item['result_value'],
                        'compliance_status' => $item['compliance_status'],
                        'updated_at' => now(),
                    ]);
            }

            DB::table('dlh_lab_orders')->where('id', $order->id)->update([
                'status' => 'COMPLETED',
                'lhu_document_url' => $validated['lhu_document_url'] ?? $order->lhu_document_url,
                'tte_status' => 'PENDING', // Menunggu tanda tangan digital Kepala UPTD
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Hasil uji laboratorium berhasil direkam. LHU siap untuk otorisasi TTE.',
            ]);
        });
    }

    /**
     * Tampilkan detail order laboratorium
     */
    public function showOrder($orderId)
    {
        $order = DB::table('dlh_lab_orders')->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        $parameters = DB::table('dlh_lab_order_parameters')
            ->join('dlh_lab_parameters', 'dlh_lab_order_parameters.parameter_id', '=', 'dlh_lab_parameters.id')
            ->where('dlh_lab_order_parameters.lab_order_id', $order->id)
            ->select(
                'dlh_lab_parameters.code',
                'dlh_lab_parameters.name',
                'dlh_lab_parameters.method',
                'dlh_lab_parameters.unit',
                'dlh_lab_order_parameters.tariff_snapshot',
                'dlh_lab_order_parameters.result_value',
                'dlh_lab_order_parameters.quality_standard_value',
                'dlh_lab_order_parameters.compliance_status'
            )
            ->get();

        $bill = $order->bill_id ? Bill::find($order->bill_id) : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'bill' => $bill,
                'parameters' => $parameters,
            ],
        ]);
    }

    /**
     * Verifikasi Publik Dokumen LHU (Scan QR Code LHU Fisik)
     */
    public function verifyPublic($orderNumber)
    {
        $order = DB::table('dlh_lab_orders')
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen LHU tidak ditemukan.'], 404);
        }

        $parameters = DB::table('dlh_lab_order_parameters')
            ->join('dlh_lab_parameters', 'dlh_lab_order_parameters.parameter_id', '=', 'dlh_lab_parameters.id')
            ->where('dlh_lab_order_parameters.lab_order_id', $order->id)
            ->select(
                'dlh_lab_parameters.name',
                'dlh_lab_parameters.unit',
                'dlh_lab_order_parameters.result_value',
                'dlh_lab_order_parameters.quality_standard_value',
                'dlh_lab_order_parameters.compliance_status'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'institution' => 'UPTD Laboratorium Lingkungan Hidup Kota Baubau',
                'order_number' => $order->order_number,
                'applicant_name' => $order->applicant_name,
                'institution_name' => $order->institution_name,
                'sample_matrix' => $order->sample_matrix,
                'sampling_location' => $order->sampling_location,
                'sampling_date' => $order->sampling_date,
                'status' => $order->status,
                'tte_status' => $order->tte_status,
                'completed_at' => $order->completed_at,
                'parameters' => $parameters,
            ],
        ]);
    }
}


<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DlhSpatialMonitoringController extends Controller
{
    /**
     * Endpoint Peta Kepatuhan Spasial Persampahan DLH (GeoJSON Format)
     * GET /api/v1/dlh/spatial/compliance-map
     */
    public function getComplianceMap(Request $request)
    {
        $district = $request->query('district');
        $subDistrict = $request->query('sub_district');
        $period = $request->query('period', Carbon::now()->format('Y-m'));

        $query = TaxObject::with(['taxpayer', 'bills' => function ($q) use ($period) {
            $q->where('period', $period);
        }])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where(function ($q) {
            $q->where('retribution_classification_id', 198) // Retribusi Persampahan
              ->orWhere('opd_id', 4);
        });

        if ($district) {
            $query->where('district', $district);
        }
        if ($subDistrict) {
            $query->where('sub_district', $subDistrict);
        }

        $objects = $query->get();

        $features = [];
        $now = Carbon::now();

        foreach ($objects as $obj) {
            $latestBill = $obj->bills->first();

            $complianceStatus = 'PENDING';
            $markerColor = '#9CA3AF'; // Abu-abu (Belum ada tagihan / Pending)

            if ($latestBill) {
                if ($latestBill->status === 'PAID') {
                    $complianceStatus = 'PAID';
                    $markerColor = '#10B981'; // Hijau (Lunas)
                } elseif ($latestBill->status === 'UNPAID') {
                    $dueDate = $latestBill->due_date ? Carbon::parse($latestBill->due_date) : null;
                    if ($dueDate && $now->gt($dueDate)) {
                        $complianceStatus = 'OVERDUE';
                        $markerColor = '#EF4444'; // Merah (Menunggak)
                    } else {
                        $complianceStatus = 'UNPAID_CURRENT';
                        $markerColor = '#F59E0B'; // Kuning/Amber (Belum bayar, belum lewat jatuh tempo)
                    }
                }
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $obj->longitude,
                        (float) $obj->latitude,
                    ],
                ],
                'properties' => [
                    'tax_object_id' => $obj->id,
                    'nop' => $obj->nop,
                    'object_name' => $obj->name,
                    'address' => $obj->address,
                    'district' => $obj->district,
                    'sub_district' => $obj->sub_district,
                    'building_category' => $obj->building_category,
                    'tipe_penghuni' => $obj->tipe_penghuni,
                    'status_hunian' => $obj->status_hunian,
                    'taxpayer_name' => $obj->taxpayer?->name ?? $obj->penanggung_jawab_nama,
                    'contact_phone' => $obj->penanggung_jawab_kontak ?? $obj->taxpayer?->phone,
                    'period' => $period,
                    'compliance_status' => $complianceStatus,
                    'marker_color' => $markerColor,
                    'bill_amount' => $latestBill ? (float) $latestBill->amount : 0,
                    'penalty_amount' => $latestBill ? (float) $latestBill->penalty_amount : 0,
                    'bill_number' => $latestBill?->bill_number,
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'properties' => [
                'opd' => 'Dinas Lingkungan Hidup Kota Baubau',
                'service' => 'Peta Kepatuhan Retribusi Persampahan',
                'period' => $period,
                'total_persil' => count($features),
                'summary' => [
                    'paid' => collect($features)->where('properties.compliance_status', 'PAID')->count(),
                    'overdue' => collect($features)->where('properties.compliance_status', 'OVERDUE')->count(),
                    'unpaid_current' => collect($features)->where('properties.compliance_status', 'UNPAID_CURRENT')->count(),
                    'pending' => collect($features)->where('properties.compliance_status', 'PENDING')->count(),
                ],
            ],
            'features' => $features,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SpptRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SpptRecordController extends Controller
{
    /**
     * Daftar SPPT dengan filter: nop, tax_year, payment_status, bpn_sync_status, search.
     */
    public function index(Request $request)
    {
        $query = SpptRecord::query();

        if ($request->has('nop')) {
            $query->where('nop', $request->nop);
        }
        if ($request->has('tax_year')) {
            $query->where('tax_year', $request->tax_year);
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->has('bpn_sync_status')) {
            $query->where('bpn_sync_status', $request->bpn_sync_status);
        }
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $s = $request->search;
                $q->where('nop', 'like', "%{$s}%")
                  ->orWhere('taxpayer_name', 'like', "%{$s}%")
                  ->orWhere('object_address', 'like', "%{$s}%")
                  ->orWhere('kelurahan', 'like', "%{$s}%")
                  ->orWhere('kecamatan', 'like', "%{$s}%");
            });
        }

        return response()->json($query->orderByDesc('tax_year')->orderBy('nop')->paginate(20));
    }

    /**
     * Simpan SPPT (manual input / SPPT scan / BPN sync).
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Hitung otomatis kalau rincian fisik lengkap
        $record = new SpptRecord($data);
        if ($record->land_area_m2 && $record->land_njop_per_m2) {
            $calc = $record->calculateTax($data['njoptkp'] ?? 0);
            $record->total_land_njop = $calc['total_land_njop'];
            $record->total_building_njop = $calc['total_building_njop'];
            $record->njoptkp = $calc['njoptkp'];
            $record->taxable_njop = $calc['taxable_njop'];
            $record->tax_amount = $calc['tax_amount'];
        }
        $record->save();

        return response()->json($record, 201);
    }

    /**
     * Detail SPPT by id (atau by NOP via ?nop=).
     */
    public function show(Request $request, $id)
    {
        $record = $request->has('by_nop')
            ? SpptRecord::where('nop', $id)->first()
            : SpptRecord::find($id);

        if (!$record) {
            return response()->json(['message' => 'SPPT tidak ditemukan'], 404);
        }
        return response()->json($record);
    }

    /**
     * Update SPPT.
     */
    public function update(Request $request, $id)
    {
        $record = SpptRecord::findOrFail($id);
        $data = $this->validated($request, $record->id);

        $record->fill($data);
        if ($record->land_area_m2 && $record->land_njop_per_m2) {
            $calc = $record->calculateTax($data['njoptkp'] ?? $record->njoptkp ?? 0);
            $record->total_land_njop = $calc['total_land_njop'];
            $record->total_building_njop = $calc['total_building_njop'];
            $record->njoptkp = $calc['njoptkp'];
            $record->taxable_njop = $calc['taxable_njop'];
            $record->tax_amount = $calc['tax_amount'];
        }
        $record->save();

        return response()->json($record);
    }

    /**
     * Hapus SPPT.
     */
    public function destroy($id)
    {
        SpptRecord::findOrFail($id)->delete();
        return response()->json(['message' => 'SPPT dihapus'], 200);
    }

    /**
     * Rekonsiliasi: bandingkan NOP lokal dengan tabel BPN (bpn_h2h_mappings / taxpayer_pbb_objects).
     * Jika cocok → synced. Jika beda luas/NJOP → conflict + tandai field. Jika tidak ada → not_found.
     */
    public function reconcile($id)
    {
        $record = SpptRecord::findOrFail($id);

        // Cari data pembanding dari taxpayer_pbb_objects (sumber BPN/legacy)
        $bpnObject = DB::table('taxpayer_pbb_objects')
            ->where('nop', $record->nop)
            ->first();

        $conflicts = [];
        $status = 'not_found';

        if ($bpnObject) {
            $status = 'synced';

            $check = [
                'land_area_m2' => $bpnObject->land_area_m2 ?? null,
                'building_area_m2' => $bpnObject->building_area_m2 ?? null,
            ];
            foreach ($check as $field => $bpnValue) {
                $localValue = $record->{$field};
                if ($bpnValue !== null && $localValue !== null
                    && abs((float) $localValue - (float) $bpnValue) > 0.01) {
                    $conflicts[$field] = [
                        'local' => $localValue,
                        'bpn' => $bpnValue,
                    ];
                }
            }
            if (count($conflicts) > 0) {
                $status = 'conflict';
            }
        }

        $record->bpn_sync_status = $status;
        $record->conflict_fields = count($conflicts) ? $conflicts : null;
        $record->bpn_last_synced_at = now();
        $record->save();

        return response()->json([
            'message' => 'Rekonsiliasi selesai',
            'bpn_sync_status' => $status,
            'conflict_fields' => $conflicts,
            'bpn_object_found' => (bool) $bpnObject,
        ]);
    }

    /**
     * Statistik ringkas SPPT.
     */
    public function stats()
    {
        return response()->json([
            'total' => SpptRecord::count(),
            'by_status' => SpptRecord::selectRaw('payment_status, count(*) as total')->groupBy('payment_status')->get(),
            'by_sync' => SpptRecord::selectRaw('bpn_sync_status, count(*) as total')->groupBy('bpn_sync_status')->get(),
            'total_tax_amount' => SpptRecord::sum('tax_amount'),
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nop' => ['required', 'string', 'max:18',
                Rule::unique('sppt_records', 'nop')->ignore($ignoreId)],
            'taxpayer_name' => 'nullable|string|max:255',
            'taxpayer_address' => 'nullable|string|max:255',
            'object_address' => 'nullable|string|max:255',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'tax_year' => 'nullable|string|max:4',
            'land_area_m2' => 'nullable|numeric|min:0',
            'building_area_m2' => 'nullable|numeric|min:0',
            'land_class' => 'nullable|string|max:50',
            'building_class' => 'nullable|string|max:50',
            'land_njop_per_m2' => 'nullable|numeric|min:0',
            'building_njop_per_m2' => 'nullable|numeric|min:0',
            'njoptkp' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'due_date' => 'nullable|date',
            'payment_locations' => 'nullable|array',
            'payment_status' => 'nullable|in:unpaid,paid,partial,overdue',
            'data_source' => 'nullable|in:bpn_sync,manual_input,sppt_scan',
            'bpn_sync_status' => 'nullable|in:synced,pending,conflict,not_found',
        ]);
    }
}

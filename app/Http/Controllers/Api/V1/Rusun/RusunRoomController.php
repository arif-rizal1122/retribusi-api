<?php

namespace App\Http\Controllers\Api\V1\Rusun;

use App\Http\Controllers\Controller;
use App\Models\RusunRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RusunRoomController extends Controller
{
    private const COMPLEX_NAMES = [
        'kotamara' => 'Rusunawa Kotamara',
        'mahasiswa' => 'Rusunawa Mahasiswa',
        'wameo' => 'Rusunawa Wameo',
        'asn' => 'Rusunawa ASN',
    ];

    /**
     * Daftar kompleks Rusunawa yang tersedia.
     */
    public function complexes(): JsonResponse
    {
        if (!Schema::hasTable('rusun_rooms')) {
            $data = collect(['kotamara', 'mahasiswa', 'wameo', 'asn'])->map(function ($code) {
                return [
                    'code' => $code,
                    'name' => self::COMPLEX_NAMES[$code] ?? ucfirst($code),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        $codes = RusunRoom::select('complex_code')
            ->distinct()
            ->pluck('complex_code');

        // Fallback default jika tabel belum di-seed
        if ($codes->isEmpty()) {
            $codes = collect(['kotamara', 'mahasiswa', 'wameo', 'asn']);
        }

        $data = $codes->map(function ($code) {
            return [
                'code' => $code,
                'name' => self::COMPLEX_NAMES[$code] ?? ucfirst($code),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Daftar lantai berdasarkan kompleks (dinamis per kompleks).
     */
    public function floors(Request $request): JsonResponse
    {
        $complex = strtolower($request->input('complex_code') ?? $request->input('complex') ?? '');
        if (empty($complex)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter complex atau complex_code wajib diisi.',
            ], 422);
        }

        if (!Schema::hasTable('rusun_rooms')) {
            $floors = $complex === 'kotamara' ? collect([1, 2]) : collect([1, 2, 3, 4]);
            $data = $floors->map(function ($f) {
                return [
                    'floor' => (int) $f,
                    'label' => 'Lantai ' . $f,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'complex' => $complex,
                'data' => $data,
            ]);
        }

        $floors = RusunRoom::where('complex_code', $complex)
            ->select('floor')
            ->distinct()
            ->orderBy('floor', 'asc')
            ->pluck('floor');

        // Fallback jika belum di-seed
        if ($floors->isEmpty()) {
            $floors = $complex === 'kotamara' ? collect([1, 2]) : collect([1, 2, 3, 4]);
        }

        $data = $floors->map(function ($f) {
            return [
                'floor' => (int) $f,
                'label' => 'Lantai ' . $f,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'complex' => $complex,
            'data' => $data,
        ]);
    }

    /**
     * Daftar kamar berstatus 'available' saja untuk form sewa warga.
     */
    public function availableRooms(Request $request): JsonResponse
    {
        $complex = strtolower($request->input('complex_code') ?? $request->input('complex') ?? '');
        if (empty($complex)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter complex atau complex_code wajib diisi.',
            ], 422);
        }

        $request->validate([
            'floor' => 'required|integer',
        ]);

        $floor = (int) $request->input('floor');

        if (!Schema::hasTable('rusun_rooms')) {
            return response()->json([
                'success' => true,
                'complex' => $complex,
                'floor' => $floor,
                'data' => [],
            ]);
        }

        $rooms = RusunRoom::with(['assetItem:id,name,code,tarif_default,satuan_tarif,lokasi'])
            ->where('complex_code', $complex)
            ->where('floor', $floor)
            ->where('status', 'available')
            ->orderBy('room_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'complex' => $complex,
            'floor' => $floor,
            'data' => $rooms,
        ]);
    }

    /**
     * Daftar semua kamar untuk admin Disperkim (scope OPD).
     */
    public function adminRooms(Request $request): JsonResponse
    {
        if (!Schema::hasTable('rusun_rooms')) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $query = RusunRoom::with([
            'assetItem:id,name,code,tarif_default,opd_id',
            'currentRental.taxpayer:id,name,phone,nik',
        ]);

        $complex = $request->input('complex_code') ?? $request->input('complex');
        if ($complex) {
            $query->where('complex_code', strtolower($complex));
        }

        if ($request->filled('floor')) {
            $query->where('floor', (int) $request->input('floor'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $rooms = $query->orderBy('complex_code')
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        $taxpayerIds = $rooms->pluck('currentRental.taxpayer_id')->filter()->unique()->values();

        $unpaidStats = collect();
        if ($taxpayerIds->isNotEmpty()) {
            $unpaidStats = \App\Models\Bill::whereIn('taxpayer_id', $taxpayerIds)
                ->where('opd_id', 11)
                ->whereIn('status', ['pending', 'overdue', 'unpaid'])
                ->selectRaw('taxpayer_id, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('taxpayer_id')
                ->get()
                ->keyBy('taxpayer_id');
        }

        $data = $rooms->map(function ($room) use ($unpaidStats) {
            $taxpayerId = $room->currentRental?->taxpayer_id;
            $stat = $taxpayerId ? $unpaidStats->get($taxpayerId) : null;
            $unpaidCount = $stat ? (int) $stat->count : 0;
            $unpaidAmount = $stat ? (float) $stat->total : 0;

            $arr = $room->toArray();
            $arr['complex_code'] = strtoupper($room->complex_code);
            $arr['monthly_rate'] = $room->assetItem ? (float) $room->assetItem->tarif_default : 0;
            $arr['unpaid_rentals_count'] = $unpaidCount;

            if ($room->currentRental) {
                $arr['current_rental']['sisa_pembayaran'] = $unpaidAmount;
            }

            return $arr;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Ubah status kamar menjadi pemeliharaan (maintenance).
     */
    public function setMaintenance(Request $request, int $id): JsonResponse
    {
        $room = RusunRoom::findOrFail($id);

        if ($room->status === 'occupied') {
            return response()->json([
                'success' => false,
                'message' => 'Kamar sedang ditempati penghuni, tidak bisa diubah ke pemeliharaan.',
            ], 422);
        }

        $room->update([
            'status' => 'maintenance',
            'notes' => $request->input('notes', $room->notes),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Kamar {$room->room_number} diset ke status Pemeliharaan.",
            'data' => $room,
        ]);
    }

    /**
     * Kembalikan status kamar dari pemeliharaan ke available.
     */
    public function setAvailable(Request $request, int $id): JsonResponse
    {
        $room = RusunRoom::findOrFail($id);

        if ($room->status === 'occupied') {
            return response()->json([
                'success' => false,
                'message' => 'Kamar sedang ditempati penghuni.',
            ], 422);
        }

        $room->update([
            'status' => 'available',
            'notes' => $request->input('notes', $room->notes),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Kamar {$room->room_number} kembali tersedia.",
            'data' => $room,
        ]);
    }
}

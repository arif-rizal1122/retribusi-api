<?php

namespace App\Http\Controllers\Api\V1\Rusun;

use App\Http\Controllers\Controller;
use App\Models\RusunRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $request->validate([
            'complex' => 'required|string',
        ]);

        $complex = strtolower($request->input('complex'));

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
        $request->validate([
            'complex' => 'required|string',
            'floor' => 'required|integer',
        ]);

        $complex = strtolower($request->input('complex'));
        $floor = (int) $request->input('floor');

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
        $query = RusunRoom::with([
            'assetItem:id,name,code,tarif_default,opd_id',
            'currentRental.taxpayer:id,name,phone,nik',
        ]);

        if ($request->filled('complex')) {
            $query->where('complex_code', strtolower($request->input('complex')));
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

        return response()->json([
            'success' => true,
            'data' => $rooms,
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

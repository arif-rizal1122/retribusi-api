<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetItem;
use Illuminate\Http\Request;

class AssetItemController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetItem::with('opd:id,name,code');

        $user = $request->user();
        if ($user && in_array($user->role, ['petugas', 'opd']) && !$user->isSuperAdmin()) {
            $query->where('opd_id', $user->opd_id);
        }

        if ($request->has('status')) {
            $query->where('status_operasional', $request->status);
        }

        if ($request->has('category_id')) {
            $categoryId = (int) $request->category_id;
            $categoryMap = $this->categoryIdMap();
            if (isset($categoryMap[$categoryId])) {
                $query->where('category', $categoryMap[$categoryId]);
            }
        }

        if ($request->has('category') && is_string($request->category)) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('only_active', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'opd_id' => 'required|exists:opds,id',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'merk_type' => 'nullable|string|max:150',
            'spesifikasi' => 'nullable|string',
            'kondisi' => 'nullable|string|max:50',
            'status_operasional' => 'nullable|string|max:50',
            'lokasi' => 'nullable|string|max:255',
            'tarif' => 'required|numeric|min:0',
            'satuan_tarif' => 'nullable|string|max:20',
            'wajib_tronton' => 'nullable|boolean',
            'image_url' => 'nullable|string|max:500',
            'pic' => 'nullable|string|max:150',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_demo' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!$user->isSuperAdmin() && $validated['opd_id'] !== $user->opd_id) {
            return response()->json(['message' => 'Akses Ditolak: OPD tidak sesuai.'], 403);
        }

        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = AssetItem::create($validated);

        return response()->json([
            'message' => 'Item aset berhasil dibuat.',
            'data' => $item,
        ], 201);
    }

    public function show(int $id)
    {
        $item = AssetItem::with('opd:id,name,code')->findOrFail($id);

        return response()->json(['data' => $item]);
    }

    public function update(Request $request, int $id)
    {
        $item = AssetItem::findOrFail($id);

        $user = $request->user();
        if (!$user->isSuperAdmin() && $item->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'merk_type' => 'nullable|string|max:150',
            'spesifikasi' => 'nullable|string',
            'kondisi' => 'nullable|string|max:50',
            'status_operasional' => 'nullable|string|max:50',
            'lokasi' => 'nullable|string|max:255',
            'tarif' => 'nullable|numeric|min:0',
            'satuan_tarif' => 'nullable|string|max:20',
            'wajib_tronton' => 'nullable|boolean',
            'image_url' => 'nullable|string|max:500',
            'pic' => 'nullable|string|max:150',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_demo' => 'nullable|boolean',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Item aset berhasil diperbarui.',
            'data' => $item,
        ]);
    }

    public function destroy(int $id)
    {
        $item = AssetItem::findOrFail($id);

        if ($item->rentals()->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Item aset tidak dapat dihapus karena masih memiliki kontrak sewa aktif.',
            ], 422);
        }

        $item->delete();

        return response()->json(['message' => 'Item aset berhasil dihapus.']);
    }

    private function categoryIdMap(): array
    {
        return [
            1 => 'alat-berat',
            2 => 'kendaraan',
            3 => 'sedot-kakus',
        ];
    }
}
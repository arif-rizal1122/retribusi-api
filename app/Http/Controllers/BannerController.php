<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BannerController extends Controller
{
    /**
     * Pastikan tabel banners ada (aman di dev yang belum migrasi).
     */
    private function tableExists(): bool
    {
        return Schema::hasTable('banners');
    }

    /**
     * Daftar banner publik (hanya yang aktif & dalam periode tayang).
     * GET /api/public/banners
     */
    public function indexPublic(Request $request)
    {
        if (!$this->tableExists()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $now = now();
        $banners = DB::table('banners')
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $banners]);
    }

    /**
     * Daftar semua banner (admin).
     * GET /api/banners
     */
    public function index(Request $request)
    {
        if (!$this->tableExists()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $banners = DB::table('banners')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $banners]);
    }

    /**
     * Buat banner baru (admin).
     * POST /api/banners
     */
    public function store(Request $request)
    {
        if (!$this->tableExists()) {
            return response()->json(['success' => false, 'message' => 'Fitur banner belum tersedia'], 503);
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'subtitle'       => 'nullable|string|max:255',
            'image_url'      => 'nullable|string|max:500',
            'link_url'       => 'nullable|string|max:500',
            'gradient_start' => 'nullable|string|max:20',
            'gradient_end'   => 'nullable|string|max:20',
            'sort_order'     => 'nullable|integer',
            'is_active'      => 'nullable|boolean',
            'start_at'       => 'nullable|date',
            'end_at'         => 'nullable|date',
        ]);

        $id = DB::table('banners')->insertGetId([
            'title'          => $validated['title'],
            'subtitle'       => $validated['subtitle'] ?? null,
            'image_url'      => $validated['image_url'] ?? null,
            'link_url'       => $validated['link_url'] ?? null,
            'gradient_start' => $validated['gradient_start'] ?? '#2563eb',
            'gradient_end'   => $validated['gradient_end'] ?? '#4f46e5',
            'sort_order'     => $validated['sort_order'] ?? 0,
            'is_active'      => $validated['is_active'] ?? true,
            'start_at'       => $validated['start_at'] ?? null,
            'end_at'         => $validated['end_at'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil dibuat',
            'data'    => DB::table('banners')->where('id', $id)->first(),
        ], 201);
    }

    /**
     * Update banner (admin) — termasuk toggle is_active & reorder sort_order.
     * PUT /api/banners/{id}
     */
    public function update(Request $request, $id)
    {
        if (!$this->tableExists()) {
            return response()->json(['success' => false, 'message' => 'Fitur banner belum tersedia'], 503);
        }

        $banner = DB::table('banners')->where('id', $id)->first();
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'subtitle'       => 'nullable|string|max:255',
            'image_url'      => 'nullable|string|max:500',
            'link_url'       => 'nullable|string|max:500',
            'gradient_start' => 'nullable|string|max:20',
            'gradient_end'   => 'nullable|string|max:20',
            'sort_order'     => 'sometimes|integer',
            'is_active'      => 'sometimes|boolean',
            'start_at'       => 'nullable|date',
            'end_at'         => 'nullable|date',
        ]);

        $data = array_intersect_key($validated, array_flip([
            'title', 'subtitle', 'image_url', 'link_url',
            'gradient_start', 'gradient_end', 'sort_order', 'is_active', 'start_at', 'end_at',
        ]));
        $data['updated_at'] = now();

        DB::table('banners')->where('id', $id)->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil diperbarui',
            'data'    => DB::table('banners')->where('id', $id)->first(),
        ]);
    }

    /**
     * Hapus banner (admin).
     * DELETE /api/banners/{id}
     */
    public function destroy(Request $request, $id)
    {
        if (!$this->tableExists()) {
            return response()->json(['success' => false, 'message' => 'Fitur banner belum tersedia'], 503);
        }

        $deleted = DB::table('banners')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Banner tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Banner berhasil dihapus']);
    }
}

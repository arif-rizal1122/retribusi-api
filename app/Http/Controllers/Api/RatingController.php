<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Taxpayer;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Simpan rating dari citizen (wajib pajak) yang login.
     * Kategori: kinerja_aplikasi | kanal_bank | petugas_opd
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint rating ini hanya untuk wajib pajak.');

        $validated = $request->validate([
            'category' => 'required|in:kinerja_aplikasi,kanal_bank,petugas_opd',
            'sub_item' => 'required|string|max:191',
            'score' => 'required|integer|min:1|max:5',
            'issue' => 'nullable|string|max:191',
            'message' => 'nullable|string',
        ]);

        $rating = Rating::create([
            'taxpayer_id' => $user->id,
            'category' => $validated['category'],
            'sub_item' => $validated['sub_item'],
            'score' => $validated['score'],
            'issue' => $validated['issue'] ?? null,
            'message' => $validated['message'] ?? null,
        ]);

        return response()->json([
            'message' => 'Rating berhasil dikirim',
            'data' => $rating,
        ], 201);
    }

    /**
     * Riwayat rating milik citizen yang login (hanya data sendiri).
     */
    public function citizenIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint rating ini hanya untuk wajib pajak.');

        $ratings = Rating::query()
            ->where('taxpayer_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($ratings);
    }

    /**
     * Semua rating untuk admin/pengawas (paginate).
     */
    public function index(Request $request)
    {
        $query = Rating::with('taxpayer:id,name,npwpd');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 15)));
    }
}

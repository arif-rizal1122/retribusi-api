<?php

namespace App\Http\Controllers;

use App\Models\PetugasTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PetugasTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PetugasTask::with(['user', 'zone', 'taxpayer', 'creator']);

        // Jika dia role petugas, hanya lihat tugasnya sendiri
        if ($user->role === 'petugas') {
            $query->where('user_id', $user->id);
        } elseif (!$user->isSuperAdmin()) {
            // Jika bukan super_admin/admin, hanya lihat tugas di OPD-nya
            $query->whereHas('user', function($q) use ($user) {
                $q->where('opd_id', $user->opd_id);
            });
        }

        // Filter status all, pending, completed
        if ($request->has('status') && in_array($request->status, ['pending', 'completed'])) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('due_date', 'asc')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isPengawas() && $user->role !== 'opd') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'taxpayer_id' => 'nullable|exists:taxpayers,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $targetUser = \App\Models\User::withoutGlobalScope(\App\Models\Scopes\RetributionTypeScope::class)->find($request->user_id);

        if (!$targetUser) {
            return response()->json(['message' => 'Petugas tidak ditemukan di dalam sistem.'], 404);
        }

        // Enforce OPD scoping for non-super-admins
        if (!$user->isSuperAdmin() && $targetUser->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Akses Ditolak: Anda hanya bisa menugaskan petugas pada OPD Anda sendiri.'], 403);
        }

        if ($user->role === 'admin' && $user->retribution_type_id && $targetUser->retribution_type_id !== $user->retribution_type_id) {
            return response()->json(['message' => 'Akses Ditolak: Anda hanya bisa menugaskan petugas pada Tipe Pajak/Wilayah Anda sendiri.'], 403);
        }

        $validated['created_by'] = $user->id;
        $validated['status'] = 'pending';

        $task = PetugasTask::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil dibuat',
            'data' => $task->load(['user', 'zone', 'taxpayer'])
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $task = PetugasTask::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $user = Auth::user();

        // Hanya petugas bersangkutan atau admin yang boleh update
        if ($user->role === 'petugas' && $task->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to update this task'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,completed',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($validated['status'] === 'completed' && $task->status === 'pending') {
            $validated['completed_at'] = now();
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('tasks/photos', 'public');
                $validated['completion_photo_path'] = $path;
            }
        } elseif ($validated['status'] === 'pending') {
            $validated['completed_at'] = null;
            $validated['completion_photo_path'] = null;
        }

        $task->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tugas berhasil diupdate',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         $task = PetugasTask::find($id);
         if (!$task) {
             return response()->json(['message' => 'Task not found'], 404);
         }

         $user = Auth::user();
         if (!$user->isSuperAdmin() && !$user->isPengawas() && $user->role !== 'opd') {
             return response()->json(['message' => 'Unauthorized deletion'], 403);
         }

         // Enforce OPD scoping for non-super-admins
         if (!$user->isSuperAdmin() && $task->user->opd_id !== $user->opd_id) {
             return response()->json(['message' => 'Unauthorized deletion of other OPD tasks'], 403);
         }

         $task->delete();

         return response()->json([
             'status' => 'success',
             'message' => 'Tugas dihapus'
         ]);
    }
}

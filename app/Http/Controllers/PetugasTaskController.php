<?php

namespace App\Http\Controllers;

use App\Models\PetugasTask;
use Illuminate\Http\Request;

class PetugasTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PetugasTask::with(['user', 'zone', 'taxpayer']);

        if ($user->role === 'petugas') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('due_date', 'asc')->paginate($request->get('per_page', 15));

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Admin or super_admin only
        if (!in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'taxpayer_id' => 'nullable|exists:taxpayers,id',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if (!$request->zone_id && !$request->taxpayer_id) {
            return response()->json(['message' => 'Zone or Taxpayer is required'], 422);
        }

        $task = PetugasTask::create([
            'user_id' => $request->user_id,
            'zone_id' => $request->zone_id,
            'taxpayer_id' => $request->taxpayer_id,
            'status' => 'pending',
            'due_date' => $request->due_date,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Tugas berhasil ditugaskan',
            'data' => $task->load(['user', 'zone', 'taxpayer'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $task = PetugasTask::with(['user', 'zone', 'taxpayer'])->findOrFail($id);

        if ($request->user()->role === 'petugas' && $task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $task]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $task = PetugasTask::findOrFail($id);
        $user = $request->user();

        // Petugas can only update status to completed
        if ($user->role === 'petugas') {
            if ($task->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate(['status' => 'required|in:pending,completed']);
            $task->status = $request->status;
            if ($task->status === 'completed') {
                $task->completed_at = now();
            } else {
                $task->completed_at = null;
            }
            $task->save();

            return response()->json(['message' => 'Status tugas diperbarui', 'data' => $task]);
        }

        // Admin updates
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'taxpayer_id' => 'nullable|exists:taxpayers,id',
            'status' => 'sometimes|in:pending,completed',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $task->update($request->all());

        if ($request->has('status')) {
            if ($request->status === 'completed' && !$task->completed_at) {
                $task->completed_at = now();
            } else if ($request->status === 'pending') {
                $task->completed_at = null;
            }
            $task->save();
        }

        return response()->json(['message' => 'Tugas berhasil diperbarui', 'data' => $task->fresh()->load(['user', 'zone', 'taxpayer'])]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task = PetugasTask::findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'Tugas berhasil dihapus']);
    }
}

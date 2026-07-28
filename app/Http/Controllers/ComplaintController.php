<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Taxpayer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ComplaintController extends Controller
{
    public function citizenIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof Taxpayer, 403, 'Endpoint pengaduan ini hanya untuk wajib pajak.');

        $complaints = Complaint::query()
            ->where('taxpayer_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($complaints);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Complaint::with("taxpayer:id,name");

        if ($request->has("status")) {
            $query->where("status", $request->status);
        }

        if ($request->has("category")) {
            $query->where("category", $request->category);
        }

        if ($request->has("type")) {
            if ($request->type === "rating") {
                $query->whereNotNull("rating");
            }

            if ($request->type === "complaint") {
                $query->whereNull("rating");
            }
        }

        if ($request->has("search")) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where("name", "like", "%$search%")
                  ->orWhere("email", "like", "%$search%")
                  ->orWhere("complaint_text", "like", "%$search%");
            });
        }

        return response()->json($query->latest()->paginate($request->get("per_page", 15)));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            "category" => "required|string",
            "complaint_text" => "required|string",
            "rating" => "nullable|integer|min:1|max:5",
            "suggestion_text" => "nullable|string",
            "attachments" => "nullable|array",
            "latitude" => "nullable|numeric|between:-90,90",
            "longitude" => "nullable|numeric|between:-180,180",
        ]);

        $complaintData = [
            "taxpayer_id" => $user instanceof Taxpayer ? $user->id : null,
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "category" => $validated["category"],
            "complaint_text" => $validated["complaint_text"],
            "rating" => $validated["rating"] ?? null,
            "suggestion_text" => $validated["suggestion_text"] ?? null,
            "attachments" => $validated["attachments"] ?? [],
            "status" => "pending",
        ];

        if (Schema::hasColumn("complaints", "latitude")) {
            $complaintData["latitude"] = $validated["latitude"] ?? null;
        }

        if (Schema::hasColumn("complaints", "longitude")) {
            $complaintData["longitude"] = $validated["longitude"] ?? null;
        }

        try {
            $complaint = Complaint::create($complaintData);
        } catch (\Throwable $e) {
            Log::error("Complaint store failed", [
                "user_id" => $user?->id,
                "error" => $e->getMessage(),
            ]);

            return response()->json([
                "message" => "Pengaduan belum berhasil dikirim. Silakan coba lagi.",
            ], 500);
        }

        return response()->json(["message" => "Pengaduan berhasil dikirim", "data" => $complaint], 201);
    }

    public function show(Complaint $complaint)
    {
        return response()->json(["data" => $complaint->load("taxpayer")]);
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        $request->validate([
            "status" => "required|in:processing,resolved,rejected",
            "admin_notes" => "nullable|string",
        ]);

        $data = [
            "status" => $request->status,
            "admin_notes" => $request->admin_notes ?? $complaint->admin_notes,
        ];

        if ($request->status === "resolved") {
            $data["resolved_at"] = Carbon::now();
            $data["resolved_by"] = auth()->id();
        }

        $complaint->update($data);

        return response()->json(["message" => "Status pengaduan berhasil diperbarui", "data" => $complaint]);
    }
}

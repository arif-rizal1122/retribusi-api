<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComplaintController extends Controller
{
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
        ]);

        $complaint = Complaint::create([
            "taxpayer_id" => $user->role === "citizen" ? $user->id : null,
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "category" => $validated["category"],
            "complaint_text" => $validated["complaint_text"],
            "rating" => $validated["rating"] ?? null,
            "suggestion_text" => $validated["suggestion_text"] ?? null,
            "attachments" => $validated["attachments"] ?? [],
            "status" => "pending",
        ]);

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

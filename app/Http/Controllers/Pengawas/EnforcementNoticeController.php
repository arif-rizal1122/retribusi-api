<?php

namespace App\Http\Controllers\Pengawas;

use App\Http\Controllers\Controller;
use App\Models\EnforcementNotice;
use App\Models\TaxObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforcementNoticeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only Pengawas roles (and super admin, admin, petugas, opd) can view enforcement notices
        if (!$user->isSuperAdmin() && !$user->isPengawas() && $user->role !== 'petugas' && $user->role !== 'opd') {
            return response()->json(['message' => 'Unauthorized. Only Supervisor, Petugas, and OPD roles can view enforcement notices.'], 403);
        }

        $query = EnforcementNotice::with(['taxObject.taxpayer', 'creator', 'approver', 'assignedPetugas']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        // Only Pengawas roles (and super admin, admin, petugas, opd) can create enforcement notices
        if (!$user->isSuperAdmin() && !$user->isPengawas() && $user->role !== 'petugas' && $user->role !== 'opd') {
            return response()->json(['message' => 'Unauthorized. Only Supervisor, Petugas, and OPD roles can create enforcement notices.'], 403);
        }

        $validated = $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'required|in:teguran_1,teguran_2,paksa,penyitaan',
            'number' => 'required|string|unique:enforcement_notices,number',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'draft';

        $notice = EnforcementNotice::create($validated);

        return response()->json($notice, 201);
    }

    public function update(Request $request, $id)
    {
        $notice = EnforcementNotice::findOrFail($id);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('enforcement/photos', 'public');
            $validated['photo_path'] = $path;
        }

        $notice->update($validated);

        return response()->json($notice);
    }

    public function generatePDF($id, \App\Services\OfficialDocumentService $docService)
    {
        $notice = EnforcementNotice::with(['taxObject.taxpayer', 'taxObject.retributionType'])->findOrFail($id);
        
        $data = $docService->generateSPP($notice);
        
        return view('pdf.spp', $data);
    }

    public function approve($id)
    {
        $notice = EnforcementNotice::findOrFail($id);
        
        // Only Kabid (or Super Admin) can approve enforcement notices
        if (!Auth::user()->isKabid() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only Kabid can approve.'], 403);
        }

        $notice->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return response()->json($notice);
    }

    /**
     * Get enforcement history for a specific tax object
     */
    public function getHistory($tax_object_id)
    {
        $history = EnforcementNotice::with(['creator', 'approver'])
            ->where('tax_object_id', $tax_object_id)
            ->oldest()
            ->get();

        return response()->json($history);
    }
}

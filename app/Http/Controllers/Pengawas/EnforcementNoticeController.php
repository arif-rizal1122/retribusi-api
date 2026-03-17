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

        $query = EnforcementNotice::with(['taxObject.taxpayer', 'bill', 'creator', 'approver', 'assignedPetugas']);
        
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
            'bill_id' => 'nullable|exists:bills,id',
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'required|in:teguran_1,teguran_2,paksa,penyitaan,jatuh_tempo,sptpd_warning',
            'number' => 'required|string|unique:enforcement_notices,number',
            'amount_at_issue' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'proses';

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
        $notice = EnforcementNotice::with(['taxObject.taxpayer', 'bill'])->findOrFail($id);
        
        switch ($notice->type) {
            case 'teguran_1':
            case 'teguran_2':
            case 'jatuh_tempo':
                $data = $docService->generateTeguran($notice);
                $view = 'pdf.teguran';
                break;
            case 'sptpd_warning':
                $data = $docService->generateTeguranSPTPD($notice);
                $view = 'pdf.teguran_sptpd';
                break;
            case 'paksa':
            case 'penyitaan':
                $data = $docService->generateSPMP($notice);
                $view = 'pdf.spmp';
                break;
            default:
                $data = $docService->generateSPP($notice);
                $view = 'pdf.spp';
        }
        
        return $docService->renderPDF($view, $data, "{$notice->type}-{$notice->number}.pdf");
    }

    public function approve($id)
    {
        $notice = EnforcementNotice::findOrFail($id);
        
        if (!Auth::user()->isKabid() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only Kabid or Super Admin can approve.'], 403);
        }

        $notice->update([
            'status' => 'disetujui',
            'approved_by' => Auth::id(),
        ]);

        return response()->json($notice);
    }

    public function reject(Request $request, $id)
    {
        $notice = EnforcementNotice::findOrFail($id);
        
        if (!Auth::user()->isKabid() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only Kabid or Super Admin can reject.'], 403);
        }

        $request->validate(['notes' => 'required|string']);

        $notice->update([
            'status' => 'ditolak',
            'rejected_at' => now(),
            'rejection_notes' => $request->notes,
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

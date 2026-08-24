<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\WaGatewayService;

class NotarisApprovalController extends Controller
{
    /**
     * Display a listing of Notaris applications.
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // optional filter
        
        $query = User::where('role', User::ROLE_NOTARIS)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    /**
     * Approve a notaris application.
     */
    public function approve(Request $request, $id, WaGatewayService $waGateway)
    {
        $notaris = User::where('role', User::ROLE_NOTARIS)->findOrFail($id);

        if ($notaris->status === 'active') {
            return response()->json(['message' => 'Akun notaris ini sudah aktif.'], 400);
        }

        $notaris->update(['status' => 'active']);

        // Format phone number to WA format (e.g. 0812... -> 62812...)
        $phone = preg_replace('/^0/', '62', $notaris->phone);
        $message = "Halo {$notaris->name},\n\nPendaftaran Akun PPAT Anda pada sistem M-PAD Bapenda telah **DISETUJUI** dan diaktifkan oleh Kepala Bapenda.\n\nAnda sekarang dapat login ke portal Admin M-PAD untuk menggunakan Modul e-BPHTB.\n\nTerima kasih.";
        
        // Attempt to send WA notification
        try {
            $waGateway->sendMessage($phone, $message);
        } catch (\Exception $e) {
            \Log::error('Failed to send WA notification for PPAT approval: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Akun PPAT berhasil diaktifkan.',
            'data' => $notaris
        ]);
    }

    /**
     * Reject a notaris application.
     */
    public function reject(Request $request, $id, WaGatewayService $waGateway)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $notaris = User::where('role', User::ROLE_NOTARIS)->findOrFail($id);

        $notaris->update([
            'status' => 'rejected'
        ]);

        // Save rejection reason to metadata
        $metadata = $notaris->metadata ?? [];
        $metadata['rejection_reason'] = strip_tags($request->reason);
        $notaris->metadata = $metadata;
        $notaris->save();

        $phone = preg_replace('/^0/', '62', $notaris->phone);
        $message = "Halo {$notaris->name},\n\nMohon maaf, pendaftaran Akun PPAT Anda pada sistem M-PAD Bapenda **DITOLAK**.\nAlasan: {$request->reason}\n\nSilakan hubungi admin Bapenda untuk informasi lebih lanjut.";
        
        try {
            $waGateway->sendMessage($phone, $message);
        } catch (\Exception $e) {
            \Log::error('Failed to send WA notification for PPAT rejection: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Pendaftaran PPAT berhasil ditolak.',
            'data' => $notaris
        ]);
    }
}

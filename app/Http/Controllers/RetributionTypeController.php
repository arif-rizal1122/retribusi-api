<?php

namespace App\Http\Controllers;

use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\UserRetributionAssignment;
use App\Models\Payment;
use App\Models\Verification;
use App\Models\Bill;
use App\Models\SurveillanceAccount;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RetributionTypeController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * List retribution types (OPD-scoped for non-admin users)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RetributionType::with(['opd', 'classifications']);

        // Default to active types only, unless explicitly requested otherwise
        if (!$request->has('show_all')) {
            $query->where('is_active', $request->boolean('is_active', true));
        }

        // Admin OPD and Petugas only see their own retribution types
        if ($user && $user->role === 'opd') {
            $query->where('opd_id', $user->opd_id);
        } elseif ($user && $user->role === 'petugas') {
            $query->where('opd_id', $user->opd_id);
            // Filter by assigned types only if petugas has active assignments
            $assignedTypeIds = $user->assignments->pluck('retribution_type_id')->filter()->unique()->toArray();
            if (!empty($assignedTypeIds)) {
                // Check if any assigned types are actually active
                $activeAssignedCount = RetributionType::whereIn('id', $assignedTypeIds)->where('is_active', true)->count();
                if ($activeAssignedCount > 0) {
                    $query->whereIn('id', $assignedTypeIds);
                }
                // If all assigned types are inactive, fall back to showing all OPD types
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $types = $query->orderBy('name')->get();

        return response()->json(['data' => $types]);
    }

    /**
     * Store new retribution type
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'icon' => 'nullable|file|image|max:1024',
            'base_amount' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        // Use user's OPD for non-super-admins, or require opd_id for super_admin
        if ($user->isSuperAdmin()) {
            $request->validate(['opd_id' => 'required|exists:opds,id']);
            $opdId = $request->opd_id;
        } else {
            $opdId = $user->opd_id;
        }

        $iconUrl = $request->hasFile('icon')
            ? $this->cloudinary->upload($request->file('icon'), 'retribusi/icons')
            : $request->icon;

        try {
            $type = RetributionType::create([
                'opd_id' => $opdId,
                'name' => $request->name,
                'category' => $request->category,
                'icon' => $iconUrl,
                'base_amount' => $request->base_amount,
                'unit' => $request->unit,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'message' => 'Jenis retribusi berhasil ditambahkan',
                'data' => $type->load('opd')
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Retribution Type Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal membuat jenis retribusi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show single retribution type
     */
    public function show(Request $request, RetributionType $retributionType)
    {
        $user = $request->user();

        // All non-super-admins can only view their own OPD's types
        if (!$user->isSuperAdmin() && $retributionType->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $retributionType->load(['opd', 'taxpayers'])
        ]);
    }

    /**
     * Update retribution type
     */
    public function update(Request $request, RetributionType $retributionType)
    {
        $user = $request->user();

        // All non-super-admins can only update their own OPD's types
        if (!$user->isSuperAdmin() && $retributionType->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->only([
            'name', 'category', 'base_amount', 'unit', 'is_active'
        ]);

        if ($request->hasFile('icon')) {
            // Delete old icon if replaced
            if ($retributionType->icon && filter_var($retributionType->icon, FILTER_VALIDATE_URL) && str_contains($retributionType->icon, 'cloudinary')) {
                $this->cloudinary->delete($retributionType->icon);
            }
            $data['icon'] = $this->cloudinary->upload($request->file('icon'), 'retribusi/icons');
        } elseif ($request->has('icon')) {
            $data['icon'] = $request->icon;
        }

        try {
            $retributionType->update($data);

            return response()->json([
                'message' => 'Jenis retribusi berhasil diupdate',
                'data' => $retributionType->fresh()->load('opd')
            ]);
        } catch (\Exception $e) {
            \Log::error('Retribution Type Update Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui jenis retribusi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete retribution type
     */
    public function destroy(Request $request, RetributionType $retributionType)
    {
        $user = $request->user();

        // All non-super-admins can only delete their own OPD's types
        if (!$user->isSuperAdmin() && $retributionType->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            // 1. Delete assignments for this retribution type
            UserRetributionAssignment::where('retribution_type_id', $retributionType->id)->delete();

            // 2. Detach taxpayers
            $retributionType->taxpayers()->detach();

            // 3. Delete Tax Objects (which will cascade to Bills, Verifications, and Payments)
            $taxObjects = TaxObject::where('retribution_type_id', $retributionType->id)->get();
            foreach ($taxObjects as $taxObject) {
                // Delete related verification explicitly built for the object's payments
                $paymentIds = Payment::where('tax_object_id', $taxObject->id)->pluck('id');
                Verification::whereIn('payment_id', $paymentIds)->delete();

                Payment::where('tax_object_id', $taxObject->id)->delete();
                Bill::where('tax_object_id', $taxObject->id)->delete();
                SurveillanceAccount::where('tax_object_id', $taxObject->id)->delete();
                $taxObject->delete();
            }

            // 4. Delete classifications
            $retributionType->classifications()->delete();

            // 5. Finally, delete the Retribution Type
            $retributionType->delete();

            DB::commit();

            return response()->json([
                'message' => 'Jenis retribusi dan seluruh data historisnya berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus jenis retribusi: ' . $e->getMessage()
            ], 500);
        }
    }
}

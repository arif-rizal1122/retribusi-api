<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\RetributionRate;
use App\Models\RetributionClassification;
use App\Services\FormulaParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillController extends Controller
{
    protected $formulaParser;

    public function __construct(FormulaParserService $formulaParser)
    {
        $this->formulaParser = $formulaParser;
    }
    /**
     * List bills (OPD-scoped)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Bill::with(['retributionType', 'user', 'opd', 'taxObject', 'taxpayer', 'classification']);

        if ($user && in_array($user->role, ['opd', 'petugas'])) {
            $query->where('opd_id', $user->opd_id);
            
            // If petugas, filter by assigned types and classifications
            if ($user->role === 'petugas') {
                $assignments = $user->assignments;
                if ($assignments->isNotEmpty()) {
                    $query->where(function($q) use ($assignments) {
                        foreach ($assignments as $assignment) {
                            $q->orWhere(function($sq) use ($assignment) {
                                $sq->where('retribution_type_id', $assignment->retribution_type_id);
                                if ($assignment->retribution_classification_id) {
                                    $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                                }
                            });
                        }
                    });
                } else {
                    // No assignments = no bills
                    $query->whereRaw('1 = 0');
                }
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('taxpayer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $billings = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($billings);
    }

    /**
     * Generate a single bill
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Support both new (tax_object_id) and legacy (taxpayer_id + retribution_type_id) flows
        $request->validate([
            'tax_object_id' => 'required_without_all:taxpayer_id,retribution_type_id|exists:tax_objects,id',
            'taxpayer_id' => 'required_without:tax_object_id|exists:taxpayers,id',
            'retribution_type_id' => 'required_without:tax_object_id|exists:retribution_types,id',
            'amount' => 'nullable|numeric|min:0',
            'period' => 'required|string',
            'due_date' => 'required|date',
            'metadata' => 'nullable|array',
        ]);

        if ($request->tax_object_id) {
            // New flow: bill is linked to a specific tax object
            $taxObject = TaxObject::with('taxpayer')->find($request->tax_object_id);
            
            if (!$user->isSuperAdmin() && $taxObject->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $bill = Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $taxObject->taxpayer_id,
                'tax_object_id' => $taxObject->id,
                'opd_id' => $taxObject->opd_id,
                'retribution_type_id' => $taxObject->retribution_type_id,
                'retribution_classification_id' => $taxObject->retribution_classification_id,
                'bill_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $request->amount ?? $this->calculateAmount($taxObject, $request->metadata ?? []),
                'status' => 'pending',
                'period' => $request->period,
                'metadata' => $request->metadata,
                'due_date' => $request->due_date,
            ]);
        } else {
            // Legacy flow: bill is linked to taxpayer + retribution type (no specific object)
            $taxpayer = Taxpayer::find($request->taxpayer_id);
            
            if (!$user->isSuperAdmin() && $taxpayer->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $bill = Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => null,
                'opd_id' => $taxpayer->opd_id,
                'retribution_type_id' => $request->retribution_type_id,
                'bill_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $request->amount ?? $type->base_amount,
                'status' => 'pending',
                'period' => $request->period,
                'metadata' => $request->metadata,
                'due_date' => $request->due_date,
            ]);
        }

        return response()->json([
            'message' => 'Tagihan berhasil dibuat',
            'data' => $bill->load(['retributionType', 'opd', 'taxObject', 'taxpayer'])
        ], 201);
    }

    /**
     * Bulk generate bills for a retribution type based on tax objects
     */
    public function bulkStore(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'period' => 'required|string',
            'due_date' => 'required|date',
        ]);

        $type = RetributionType::find($request->retribution_type_id);

        if (!$user->isSuperAdmin() && $type->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get all active tax objects for this type
        $query = TaxObject::where('retribution_type_id', $type->id)
            ->where('status', 'active');
            
        if ($request->retribution_classification_id) {
            $query->where('retribution_classification_id', $request->retribution_classification_id);
        }

        $objects = $query->get();

        $createdCount = 0;
        foreach ($objects as $obj) {
            Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $obj->taxpayer_id,
                'tax_object_id' => $obj->id,
                'opd_id' => $type->opd_id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $obj->retribution_classification_id,
                'bill_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $this->calculateAmount($obj, $request->metadata ?? []), 
                'status' => 'pending',
                'period' => $request->period,
                'due_date' => $request->due_date,
            ]);
            $createdCount++;
        }

        return response()->json([
            'message' => "Berhasil generate {$createdCount} tagihan",
            'count' => $createdCount
        ]);
    }

    /**
     * Show bill details
     */
    public function show(Request $request, Bill $bill)
    {
        $user = $request->user();
        
        // Ownership / Authorization check
        if ($user instanceof \App\Models\User) {
            // Admin/Petugas: restrict by OPD if not super admin
            if (!$user->isSuperAdmin() && $bill->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else if ($user instanceof \App\Models\Taxpayer) {
            // Citizen: restrict by their own record
            if ($bill->taxpayer_id !== $user->id) {
                return response()->json(['message' => 'Forbidden: This is not your bill.'], 403);
            }
        }

        return response()->json([
            'data' => $bill->load(['retributionType', 'opd', 'payments', 'taxObject', 'taxpayer'])
        ]);
    }

    /**
     * List bills for a citizen (by NIK)
     */
    public function citizenBills(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $bills = Bill::with(['retributionType', 'opd', 'taxObject', 'classification'])
            ->whereHas('taxpayer', function($q) use ($request) {
                $q->where('nik', $request->nik);
            })
            ->latest()
            ->get();

        return response()->json([
            'data' => $bills
        ]);
    }

    /**
     * Export/Preview SKRD
     */
    public function exportSKRD(Request $request, Bill $bill, \App\Services\OfficialDocumentService $docService)
    {
        $user = $request->user();
        if ($user instanceof \App\Models\Taxpayer && $bill->taxpayer_id !== $user->id) {
            return abort(403, 'Unauthorized access to this document.');
        }

        $data = $docService->generateSKRD($bill->load(['retributionType', 'taxpayer']));
        
        return view('pdf.skrd', $data);
    }

    /**
     * Export/Preview SSPD
     */
    public function exportSSPD(Request $request, Bill $bill, \App\Services\OfficialDocumentService $docService)
    {
        $user = $request->user();
        if ($user instanceof \App\Models\Taxpayer && $bill->taxpayer_id !== $user->id) {
            return abort(403, 'Unauthorized access to this document.');
        }

        try {
            $data = $docService->generateSSPD($bill->load(['retributionType', 'taxpayer', 'payments']));
            return view('pdf.sspd', $data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Export/Preview SPPT (PBB)
     */
    public function exportSPPT(Request $request, Bill $bill, \App\Services\OfficialDocumentService $docService)
    {
        $user = $request->user();
        if ($user instanceof \App\Models\Taxpayer && $bill->taxpayer_id !== $user->id) {
            return abort(403, 'Unauthorized access to this document.');
        }

        try {
            // Verify if this is actually a PBB bill
            $name = strtolower($bill->retributionType->name ?? '');
            if (!str_contains($name, 'pbb') && !str_contains($name, 'pajak bumi')) {
                return response()->json(['message' => 'Hanya tagihan PBB yang dapat mencetak SPPT.'], 400);
            }

            $data = $docService->generateSPPT($bill);
            
            if ($request->query('download') === 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sppt', $data);
                return $pdf->download("SPPT-{$bill->taxObject->nop}-{$data['year']}.pdf");
            }

            return view('pdf.sppt', $data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal generate SPPT: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper to calculate bill amount based on tax object hierarchy and formulas
     */
    private function calculateAmount($taxObject, $inputData = [])
    {
        $type = $taxObject->retributionType;

        // 0. Handle PBB-P2 Special Calculation
        if (str_contains(strtolower($type->name), 'pbb') || str_contains(strtolower($type->category), 'pajak bumi')) {
            $pbbService = app(\App\Services\PbbCalculationService::class);
            $metadata = array_merge($taxObject->metadata ?? [], $inputData);
            
            $luasBumi = (float) ($metadata['luas_bumi'] ?? $metadata['luas_tanah'] ?? 0);
            $kelasBumi = (string) ($metadata['kelas_bumi'] ?? '');
            $luasBangunan = (float) ($metadata['luas_bangunan'] ?? 0);
            $kelasBangunan = (string) ($metadata['kelas_bangunan'] ?? '');
            
            // Allow overrides from metadata for NJOPTKP and Tariff
            $njoptkp = (float) ($metadata['njoptkp'] ?? 10000000);
            $tariff = (float) ($metadata['tariff'] ?? 0.001);

            $result = $pbbService->calculate($luasBumi, $kelasBumi, $luasBangunan, $kelasBangunan, $njoptkp, $tariff);
            return (float) $result['pbb_terhutang'];
        }

        // 1. Try to find a specific rate for this classification and zone
        $rate = \App\Models\RetributionRate::where('retribution_type_id', $taxObject->retribution_type_id)
            ->where('retribution_classification_id', $taxObject->retribution_classification_id)
            ->where(function($q) use ($taxObject) {
                if ($taxObject->zone_id) {
                    $q->where('zone_id', $taxObject->zone_id);
                } else {
                    $q->whereNull('zone_id');
                }
            })
            ->where('is_active', true)
            ->first();

        // 2. Determine base variables for formula
        $variables = array_merge(
            $taxObject->metadata ?? [], 
            $inputData,
            [
                'amount' => $rate ? $rate->amount : 0,
                'tariff' => $rate ? ($rate->amount / 100) : 0, // Assume amount is percent for some cases
            ]
        );

        // 3. Check for dynamic formula in Rate first
        if ($rate && $rate->calculation_formula) {
            return $this->formulaParser->calculate($rate->calculation_formula, $variables);
        }

        // 4. Check for dynamic formula in Classification
        $classification = \App\Models\RetributionClassification::find($taxObject->retribution_classification_id);
        if ($classification && $classification->calculation_formula) {
            return $this->formulaParser->calculate($classification->calculation_formula, $variables);
        }

        // 5. Fallback to fixed rate amount
        if ($rate) {
            return $rate->amount;
        }

        // 6. Final fallback to base amount of the type
        return $type ? $type->base_amount : 0;
    }

    public function signTTE(Request $request, \App\Services\OfficialDocumentService $docService)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'notes' => 'nullable|string',
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);
        
        // Authorization check (Higher authority or OPD Admin)
        $user = $request->user();
        if (!$user->isSuperAdmin() && !in_array($user->role, ['kadis', 'kabid', 'opd'])) {
            return response()->json(['message' => 'Unauthorized to sign. Higher authority required.'], 403);
        }

        try {
            $signedDoc = $docService->signDocument('bill', $bill->id, $user, $validated['notes'] ?? null);
            return response()->json([
                'message' => 'Dokumen berhasil ditandatangani secara elektronik.',
                'signed_document' => $signedDoc
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("TTE Signing Error: " . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
                'bill_id' => $bill->id
            ]);
            return response()->json(['message' => 'Gagal menandatangani: ' . $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SpotCheck;
use App\Models\SpotCheckItem;
use App\Services\SpotCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SpotCheckController extends Controller
{
    private $spotCheckService;

    public function __construct(SpotCheckService $spotCheckService)
    {
        $this->spotCheckService = $spotCheckService;
    }

    public function index(Request $request)
    {
        $query = SpotCheck::with(['taxpayer', 'taxObject', 'inspector', 'supervisor']);

        if ($request->has('tax_object_id')) {
            $query->where('tax_object_id', $request->tax_object_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $spotChecks = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($spotChecks);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'taxpayer_id' => 'required|exists:taxpayers,id',
            'tax_object_id' => 'required|exists:tax_objects,id',
            'inspector_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_weekend' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.observation_time' => 'required',
            'items.*.visitor_count' => 'required|integer|min:0',
            'items.*.transaction_count' => 'required|integer|min:0',
            'items.*.estimated_value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $spotCheck = SpotCheck::create($request->only([
                'taxpayer_id', 'tax_object_id', 'inspector_id', 
                'start_date', 'end_date', 'is_weekend', 
                'taxpayer_representative', 'remarks'
            ]));

            foreach ($request->items as $item) {
                $spotCheck->items()->create($item);
            }

            DB::commit();
            return response()->json(['message' => 'Spot check created successfully', 'data' => $spotCheck->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create spot check', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $spotCheck = SpotCheck::with(['taxpayer', 'taxObject', 'inspector', 'supervisor', 'items'])->findOrFail($id);
        return response()->json($spotCheck);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,submitted,approved',
            'supervisor_id' => 'required_if:status,approved|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $spotCheck = SpotCheck::findOrFail($id);
        $spotCheck->status = $request->status;
        
        if ($request->has('supervisor_id')) {
            $spotCheck->supervisor_id = $request->supervisor_id;
        }

        $spotCheck->save();

        return response()->json(['message' => 'Status updated successfully', 'data' => $spotCheck]);
    }

    public function getEstimatedRevenue($taxObjectId)
    {
        $data = $this->spotCheckService->calculateEstimatedMonthlyRevenue($taxObjectId);
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}

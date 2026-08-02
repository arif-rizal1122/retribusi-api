<?php

namespace App\Http\Controllers;

use App\Models\H2hConnectorLog;
use Illuminate\Http\Request;

class H2hConnectorLogController extends Controller
{
    /**
     * Log konektor H2H (limit default 50, filter connector/status).
     */
    public function index(Request $request)
    {
        $query = H2hConnectorLog::query();

        if ($request->has('connector')) {
            $query->where('connector', $request->connector);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $limit = min((int) ($request->limit ?? 50), 100);

        return response()->json([
            'status' => 'success',
            'data'   => $query->orderByDesc('id')->limit($limit)->get(),
        ]);
    }
}

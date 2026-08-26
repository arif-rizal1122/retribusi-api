<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MblbVehicle;

class MblbVehicleController extends Controller
{
    public function index()
    {
        $vehicles = MblbVehicle::where('is_active', true)->get();
        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }
}

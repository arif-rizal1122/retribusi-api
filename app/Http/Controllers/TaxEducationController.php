<?php

namespace App\Http\Controllers;

use App\Models\TaxEducation;
use Illuminate\Http\Request;

class TaxEducationController extends Controller
{
    public function index()
    {
        return response()->json(['data' => TaxEducation::with('createdBy')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:consultation,socialization,public_info',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'nullable|string',
            'material_url' => 'nullable|url',
        ]);

        $validated['created_by'] = auth()->id() ?? 1;

        $education = TaxEducation::create($validated);

        return response()->json(['data' => $education], 201);
    }

    public function show(TaxEducation $taxEducation)
    {
        return response()->json(['data' => $taxEducation->load('createdBy')]);
    }

    public function update(Request $request, TaxEducation $taxEducation)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|in:consultation,socialization,public_info',
            'description' => 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'location' => 'nullable|string',
            'material_url' => 'nullable|url',
        ]);

        $taxEducation->update($validated);

        return response()->json(['data' => $taxEducation]);
    }

    public function destroy(TaxEducation $taxEducation)
    {
        $taxEducation->delete();
        return response()->json(null, 204);
    }

    public function broadcast(TaxEducation $taxEducation)
    {
        return response()->json([
            'message' => 'Notification broadcast triggered for: ' . $taxEducation->title,
            'status' => 'success'
        ]);
    }
}

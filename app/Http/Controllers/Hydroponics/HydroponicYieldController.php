<?php

namespace App\Http\Controllers\Hydroponics;

use App\Http\Controllers\Controller;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Http\Request;

class HydroponicYieldController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $yields = HydroponicSetup::where('user_id', $user->id)
            ->with('hydroponic_yields')
            ->get()
            ->pluck('hydroponic_yields')
            ->flatten();

        return response()->json([
            'status' => 'success',
            'data' => $yields
        ]);
    }

    public function show(HydroponicSetup $setup)
    {
        $yield = $setup->hydroponic_yields;

        return response()->json([
            'status' => 'success',
            'data' => $yield
        ]);
    }

    public function updateActualYield(Request $request, HydroponicYield $yield)
    {
        $validated = $request->validate([
            'actual_yield' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['harvest_date'] = now();
        $yield->update([
            'actual_yield' => $validated['actual_yield'],
            'harvest_date' => $validated['harvest_date'],
            'harvest_status' => 'harvested',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Actual yield and harvest date recorded successfully.',
            'data' => $yield
        ]);
    }
}

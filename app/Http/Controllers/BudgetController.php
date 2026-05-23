<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;

class BudgetController extends Controller
{
    //daily, weekly, monthly targets
    public function setBudget(Request $request)
    {
        $request->validate([
            'daily_target' => 'nullable|numeric',
            'weekly_target' => 'nullable|numeric',
            'monthly_target' => 'nullable|numeric',
            'month_year' => 'required|string|size:7', // Format: YYYY-MM
        ]);

        // Bypass auth for hardcoded
        $userId = 1;

        $budget = Budget::firstOrNew(
            [
                'user_id' => $userId,
                'month_year' => $request->input('month_year')
            ]
        );

        if ($request->filled('daily_target')) {
            $budget->daily_target = $request->input('daily_target');
        }
        if ($request->filled('weekly_target')) {
            $budget->weekly_target = $request->input('weekly_target');
        }
        if ($request->filled('monthly_target')) {
            $budget->monthly_target = $request->input('monthly_target');
        }

        $budget->save();

        return response()->json(['message' => 'Budget set successfully', 'budget' => $budget]);
    }

    // Return budget history for the hardcoded user
    public function index(Request $request)
    {
        $budgets = Budget::where('user_id', 1)->orderBy('month_year', 'desc')->get();
        return response()->json(['data' => $budgets]);
    }
}

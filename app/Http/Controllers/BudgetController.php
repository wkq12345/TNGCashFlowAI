<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        // Bypass auth for hardcoded hackathon MVP
        $userId = 1;

        $budget = Budget::updateOrCreate(
            ['user_id' => $userId, 'month_year' => $request->month_year],
            [
                'daily_target' => $request->daily_target,
                'weekly_target' => $request->weekly_target,
                'monthly_target' => $request->monthly_target,
            ]
        );

        return response()->json(['message' => 'Budget set successfully', 'budget' => $budget]);
    }
}

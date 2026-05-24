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
            'alarm_scope' => 'nullable|in:daily,weekly,monthly',
            'alarm_enabled' => 'nullable|boolean',
            'alarm_mode' => 'nullable|in:percentage,amount',
            'alarm_value' => 'nullable|numeric|min:0',
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

        if ($request->filled('alarm_scope')) {
            $alarmConfig = $budget->alarm_config ?? [];
            $scope = $request->input('alarm_scope');

            $alarmConfig[$scope] = [
                'enabled' => $request->boolean('alarm_enabled', true),
                'mode' => $request->input('alarm_mode', 'percentage'),
                'value' => $request->input('alarm_value'),
                'last_triggered_threshold' => null,
            ];

            $budget->alarm_config = $alarmConfig;
        }

        $budget->save();

        return response()->json(['message' => 'Budget set successfully', 'budget' => $budget]);
    }

    public function updateAlarmState(Request $request)
    {
        $request->validate([
            'month_year' => 'required|string|size:7',
            'alarm_scope' => 'required|in:daily,weekly,monthly',
            'last_triggered_threshold' => 'required|numeric|min:0',
        ]);

        $budget = Budget::where('user_id', 1)
            ->where('month_year', $request->input('month_year'))
            ->first();

        if (!$budget) {
            return response()->json(['message' => 'Budget not found'], 404);
        }

        $alarmConfig = $budget->alarm_config ?? [];
        $scope = $request->input('alarm_scope');
        $alarmConfig[$scope] = array_merge(
            $alarmConfig[$scope] ?? [],
            ['last_triggered_threshold' => (float) $request->input('last_triggered_threshold')]
        );

        $budget->alarm_config = $alarmConfig;
        $budget->save();

        return response()->json(['message' => 'Alarm state updated', 'budget' => $budget]);
    }

    // Return budget history for the hardcoded user
    public function index(Request $request)
    {
        $budgets = Budget::where('user_id', 1)->orderBy('month_year', 'desc')->get();
        return response()->json(['data' => $budgets]);
    }
}

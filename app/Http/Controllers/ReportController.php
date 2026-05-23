<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\FixedExpense;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $monthYear = $request->input('month_year', date('Y-m'));
        $granularity = $request->input('granularity', 'month'); // day|week|month

        $year = intval(substr($monthYear, 0, 4));
        $month = intval(substr($monthYear, 5, 2));

        $budget = Budget::where('user_id', 1)->where('month_year', $monthYear)->first();

        $transactions = Transaction::with('category')->where('user_id', 1)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->get();

        $totalSpent = $transactions->sum('amount');
        $daysPassed = intval(date('j'));
        $daysInMonth = intval(date('t'));

        $paceProjected = 0;
        if ($daysPassed > 0) {
            $paceProjected = ($totalSpent / $daysPassed) * $daysInMonth;
        }

        // Build series depending on granularity
        $series = [];
        if ($granularity === 'daily') {
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $daySum = $transactions->filter(function ($t) use ($d) {
                    return intval(Carbon::parse($t->transaction_date)->day) === $d;
                })->sum('amount');

                $series[] = [
                    'label' => sprintf('%02d', $d),
                    'date' => sprintf('%04d-%02d-%02d', $year, $month, $d),
                    'amount' => round($daySum, 2)
                ];
            }
        } elseif ($granularity === 'week') {
            // Return sums per day of current week (Mon..Sun)
            $startOfWeek = Carbon::now()->startOfWeek();
            for ($i = 0; $i < 7; $i++) {
                $day = $startOfWeek->copy()->addDays($i);
                $daySum = $transactions->filter(function ($t) use ($day) {
                    return Carbon::parse($t->transaction_date)->isSameDay($day);
                })->sum('amount');
                $series[] = ['label' => $day->format('D'), 'date' => $day->toDateString(), 'amount' => round($daySum, 2)];
            }
        } else {
            // month view: group by day but only include days with data for compactness
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $daySum = $transactions->filter(function ($t) use ($d) {
                    return intval(Carbon::parse($t->transaction_date)->day) === $d;
                })->sum('amount');
                $series[] = [
                    'label' => sprintf('%02d', $d),
                    'date' => sprintf('%04d-%02d-%02d', $year, $month, $d),
                    'amount' => round($daySum, 2)
                ];
            }
        }

        // Category breakdown
        $categoryBreakdown = [];
        $transactions->groupBy(function ($transaction) {
            return $transaction->category?->name ?? 'Others';
        })->each(function ($group, $categoryName) use (&$categoryBreakdown) {
            $categoryBreakdown[$categoryName] = round($group->sum('amount'), 2);
        });

        $recent = $transactions->sortByDesc('transaction_date')->take(10)->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'merchant' => $transaction->merchant,
                'category_id' => $transaction->category_id,
                'category_name' => $transaction->category?->name ?? 'Others',
                'amount' => $transaction->amount,
                'source' => $transaction->source,
                'transaction_date' => $transaction->transaction_date,
                'raw_text' => $transaction->raw_text,
            ];
        })->values();

        $fixed = [];
        $totalFixed = 0;

        // Days in the requested month
        $daysInRequestedMonth = intval(Carbon::createFromDate($year, $month, 1)->daysInMonth);

        if (class_exists(FixedExpense::class)) {
            $rawFixed = FixedExpense::where('user_id', 1)->get();

            // Prorate logic
            foreach ($rawFixed as $expense) {
                // Determine base monthly amount based on cadence
                $monthlyAmount = $expense->amount;
                if ($expense->cadence === 'daily') {
                    $monthlyAmount = $expense->amount * $daysInRequestedMonth;
                } elseif ($expense->cadence === 'weekly') {
                    $monthlyAmount = ($expense->amount / 7) * $daysInRequestedMonth;
                }

                // Now scale to request granularity
                $proratedAmount = $monthlyAmount;
                if ($granularity === 'daily') {
                    $proratedAmount = $monthlyAmount / $daysInRequestedMonth;
                } elseif ($granularity === 'week' || $granularity === 'weekly') {
                    $proratedAmount = ($monthlyAmount / $daysInRequestedMonth) * 7;
                }

                $fixed[] = [
                    'id' => $expense->id,
                    'name' => $expense->name,
                    'cadence' => $expense->cadence,
                    'original_amount' => round($expense->amount, 2),
                    'amount' => round($proratedAmount, 2),
                    'category' => $expense->category,
                ];
                $totalFixed += $proratedAmount;
            }
        }

        // Active target calculation based on granularity
        $activeTarget = null;
        if ($budget) {
            if ($granularity === 'daily') {
                $activeTarget = $budget->daily_target ?? ($budget->monthly_target / $daysInRequestedMonth);
            } elseif ($granularity === 'week' || $granularity === 'weekly') {
                $activeTarget = $budget->weekly_target ?? (($budget->monthly_target / $daysInRequestedMonth) * 7);
            } else {
                $activeTarget = $budget->monthly_target;
            }
        }

        return response()->json([
            'budget' => $budget,
            'active_target' => $activeTarget ? round($activeTarget, 2) : 0,
            'total_spent' => round($totalSpent, 2),
            'projected_end_of_month' => round($paceProjected, 2),
            'series' => $series,
            'category_breakdown' => $categoryBreakdown,
            'recent_transactions' => $recent,
            'fixed_expenses' => $fixed,
            'total_fixed_expenses' => round($totalFixed, 2),
        ]);
    }
}

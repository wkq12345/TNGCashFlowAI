<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Budget;
use App\Models\Transaction;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Phase 1 API Endpoints (Hardcoded user_id = 1 for Hackathon MVP)

// 1. Budget Endpoints
Route::post('/budgets', function (Request $request) {
    $validated = $request->validate([
        'monthly_target' => 'required|numeric',
        'daily_target' => 'required|numeric',
        'month_year' => 'required|string|size:7'
    ]);

    $budget = Budget::updateOrCreate(
        ['user_id' => 1, 'month_year' => $validated['month_year']],
        [
            'monthly_target' => $validated['monthly_target'],
            'daily_target' => $validated['daily_target']
        ]
    );

    return response()->json(['message' => 'Budget saved', 'data' => $budget], 201);
});

// 2. Transaction Endpoints
Route::post('/transactions', function (Request $request) {
    $validated = $request->validate([
        'amount' => 'required|numeric',
        'merchant' => 'required|string',
    ]);

    // Simple Rule-based Categorization Fallback
    $category = 'Others';
    $merchantLower = strtolower($validated['merchant']);

    if (str_contains($merchantLower, 'tealive') || str_contains($merchantLower, 'zus') || str_contains($merchantLower, 'mcdonald')) {
        $category = 'Food & Drinks';
    } elseif (str_contains($merchantLower, 'rapidkl') || str_contains($merchantLower, 'grab')) {
        $category = 'Transport';
    } elseif (str_contains($merchantLower, 'watsons') || str_contains($merchantLower, 'guardian')) {
        $category = 'Health';
    }

    $transaction = Transaction::create([
        'user_id' => 1,
        'amount' => $validated['amount'],
        'merchant' => $validated['merchant'],
        'category' => $category, // In Phase 3, we'll swap this with LLM API!
        'source' => $request->input('source', 'Notification'),
        'transaction_date' => now()
    ]);

    return response()->json(['message' => 'Transaction saved', 'data' => $transaction], 201);
});

// 3. Simple Dashboard Data
Route::get('/dashboard', function (Request $request) {
    $monthYear = $request->input('month_year', date('Y-m'));

    $budget = Budget::where('user_id', 1)->where('month_year', $monthYear)->first();
    $transactions = Transaction::where('user_id', 1)
        ->whereMonth('transaction_date', substr($monthYear, 5, 2))
        ->whereYear('transaction_date', substr($monthYear, 0, 4))
        ->get();

    $totalSpent = $transactions->sum('amount');
    $daysPassed = date('d');
    $daysInMonth = date('t');

    // Pace Calculation
    $paceProjected = 0;
    if ($daysPassed > 0) {
        $paceProjected = ($totalSpent / $daysPassed) * $daysInMonth;
    }

    return response()->json([
        'budget' => $budget,
        'total_spent' => $totalSpent,
        'projected_end_of_month' => round($paceProjected, 2),
        'transactions' => $transactions
    ]);
});

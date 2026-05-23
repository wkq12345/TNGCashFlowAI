<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FixedExpense;

class FixedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $fixed = FixedExpense::where('user_id', 1)->get();
        return response()->json(['data' => $fixed]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:monthly,weekly,daily',
            'category' => 'nullable|string',
        ]);

        $fixed = FixedExpense::create([
            'user_id' => 1,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? null,
        ]);

        return response()->json(['message' => 'Fixed expense created', 'data' => $fixed], 201);
    }
}

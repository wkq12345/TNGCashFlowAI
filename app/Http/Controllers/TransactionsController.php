<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'merchant' => 'required|string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'category_name' => 'nullable|string',
            'raw_text' => 'nullable|string',
            'dedupe_hash' => 'nullable|string',
            'source' => 'nullable|string',
            'transaction_date' => 'nullable|date',
        ]);

        $dedupe = $validated['dedupe_hash'] ?? null;
        if ($dedupe && Transaction::where('dedupe_hash', $dedupe)->exists()) {
            return response()->json(['message' => 'Duplicate transaction ignored'], 200);
        }

        $categoryId = $validated['category_id'] ?? $this->resolveCategoryId(
            $validated['category_name'] ?? null,
            $validated['merchant']
        );

        $transaction = Transaction::create([
            'user_id' => 1,
            'category_id' => $categoryId,
            'amount' => $validated['amount'],
            'merchant' => $validated['merchant'],
            'source' => $validated['source'] ?? 'Notification',
            'transaction_date' => $validated['transaction_date'] ?? now(),
            'raw_text' => $validated['raw_text'] ?? null,
            'dedupe_hash' => $dedupe,
        ]);

        return response()->json([
            'message' => 'Transaction saved',
            'data' => $transaction->load('category'),
        ], 201);
    }

    private function resolveCategoryId(?string $categoryName, string $merchant): ?int
    {
        $userId = 1;

        $preferredNames = array_values(array_filter([
            $categoryName,
            'Others',
        ]));

        foreach ($preferredNames as $name) {
            $category = Category::where('user_id', $userId)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($category) {
                return $category->id;
            }
        }

        return Category::firstOrCreate([
            'user_id' => $userId,
            'name' => 'Others',
        ])->id;
    }
}

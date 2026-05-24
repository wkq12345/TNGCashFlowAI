<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FixedExpenseController;
use App\Http\Controllers\LlmController;
use App\Http\Controllers\CategoryController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Phase 1 API Endpoints (Hardcoded user_id = 1 )

// 1. Budget Endpoints
Route::post('/budgets', [BudgetController::class, 'setBudget']);
Route::post('/budgets/alarm-state', [BudgetController::class, 'updateAlarmState']);

// 2. Transaction Endpoints
Route::post('/transactions', [TransactionsController::class, 'store']);

// 3. Reports / Dashboard (more featureful endpoint)
Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);

// 4. Fixed Expenses (recurring bills)
Route::get('/fixed-expenses', [FixedExpenseController::class, 'index']);
Route::post('/fixed-expenses', [FixedExpenseController::class, 'store']);

// 5. Categories CRUD
Route::apiResource('/categories', CategoryController::class);

// Backwards-compatible simple dashboard route (keeps existing client working)
Route::get('/dashboard', [ReportController::class, 'dashboard']);

// LLM proxy endpoint used by mobile app to request structured extraction
Route::post('/llm/parse', [LlmController::class, 'parse']);

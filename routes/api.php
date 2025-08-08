<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\InsuranceImportLog;
use App\Models\InsurancePayment;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API برای بودجه باقی‌مانده
Route::get('/budget/remaining', function () {
    $totalCredit = FundingTransaction::sum('amount');
    $totalDebit = InsuranceAllocation::sum('amount') +
                  InsuranceImportLog::sum('total_insurance_amount') +
                  InsurancePayment::sum('total_amount');
    $remainingBudget = $totalCredit - $totalDebit;
    
    return response()->json([
        'remaining_budget' => $remainingBudget
    ]);
})->middleware('auth'); 
<?php

use App\Http\Controllers\Api\BIAN\CurrentAccountController;
use App\Http\Controllers\Api\BIAN\PaymentInitiationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BIAN-Compliant API Routes
|--------------------------------------------------------------------------
|
| These routes follow BIAN (Banking Industry Architecture Network) standards
| for service domains, control records, and behavior qualifiers.
|
*/

Route::middleware('auth:sanctum')->prefix('bian')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Current Account Service Domain
    |--------------------------------------------------------------------------
    | Functional Pattern: Fulfill
    | Asset Type: Current Account Fulfillment Arrangement
    */
    
    // Control Record Operations
    Route::prefix('current-account')->group(function () {
        Route::post('initiate', [CurrentAccountController::class, 'initiate']);
        Route::get('{cr-reference-id}/retrieve', [CurrentAccountController::class, 'retrieve']);
        Route::put('{cr-reference-id}/update', [CurrentAccountController::class, 'update']);
        Route::put('{cr-reference-id}/control', [CurrentAccountController::class, 'control']);
        
        // Behavior Qualifier: Payment
        Route::post('{cr-reference-id}/payment/execute', [CurrentAccountController::class, 'executePayment']);
        
        // Behavior Qualifier: Deposit
        Route::post('{cr-reference-id}/deposit/execute', [CurrentAccountController::class, 'executeDeposit']);
        
        // Behavior Qualifier: Account Balance
        Route::get('{cr-reference-id}/account-balance/retrieve', [CurrentAccountController::class, 'retrieveAccountBalance']);
        
        // Behavior Qualifier: Transaction Report
        Route::get('{cr-reference-id}/transaction-report/retrieve', [CurrentAccountController::class, 'retrieveTransactionReport']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Payment Initiation Service Domain
    |--------------------------------------------------------------------------
    | Functional Pattern: Transact
    | Asset Type: Payment Transaction
    */
    
    // Control Record Operations
    Route::prefix('payment-initiation')->group(function () {
        Route::post('initiate', [PaymentInitiationController::class, 'initiate']);
        Route::get('{cr-reference-id}/retrieve', [PaymentInitiationController::class, 'retrieve']);
        Route::put('{cr-reference-id}/update', [PaymentInitiationController::class, 'update']);
        Route::post('{cr-reference-id}/execute', [PaymentInitiationController::class, 'execute']);
        
        // Behavior Qualifier: Payment Status
        Route::post('{cr-reference-id}/payment-status/request', [PaymentInitiationController::class, 'requestPaymentStatus']);
        
        // Behavior Qualifier: Payment History
        Route::get('{account-reference}/payment-history/retrieve', [PaymentInitiationController::class, 'retrievePaymentHistory']);
    });
});
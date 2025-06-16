<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountBalanceController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\CustodianController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Account management endpoints
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{uuid}', [AccountController::class, 'show']);
    Route::delete('/accounts/{uuid}', [AccountController::class, 'destroy']);
    Route::post('/accounts/{uuid}/freeze', [AccountController::class, 'freeze']);
    Route::post('/accounts/{uuid}/unfreeze', [AccountController::class, 'unfreeze']);

    // Transaction endpoints
    Route::post('/accounts/{uuid}/deposit', [TransactionController::class, 'deposit']);
    Route::post('/accounts/{uuid}/withdraw', [TransactionController::class, 'withdraw']);
    Route::get('/accounts/{uuid}/transactions', [TransactionController::class, 'history']);

    // Transfer endpoints
    Route::post('/transfers', [TransferController::class, 'store']);
    Route::get('/transfers/{uuid}', [TransferController::class, 'show']);
    Route::get('/accounts/{uuid}/transfers', [TransferController::class, 'history']);

    // Balance inquiry endpoints (legacy)
    Route::get('/accounts/{uuid}/balance', [BalanceController::class, 'show']);
    Route::get('/accounts/{uuid}/balance/summary', [BalanceController::class, 'summary']);
    
    // Multi-asset balance endpoints
    Route::get('/accounts/{uuid}/balances', [AccountBalanceController::class, 'show']);
    Route::get('/balances', [AccountBalanceController::class, 'index']);
    
    // Custodian integration endpoints
    Route::prefix('custodians')->group(function () {
        Route::get('/', [CustodianController::class, 'index']);
        Route::get('/{custodian}/account-info', [CustodianController::class, 'accountInfo']);
        Route::get('/{custodian}/balance', [CustodianController::class, 'balance']);
        Route::post('/{custodian}/transfer', [CustodianController::class, 'transfer']);
        Route::get('/{custodian}/transactions', [CustodianController::class, 'transactionHistory']);
        Route::get('/{custodian}/transactions/{transactionId}', [CustodianController::class, 'transactionStatus']);
    });
});

// Public asset and exchange rate endpoints (no auth required for read-only access)
Route::prefix('v1')->group(function () {
    // Asset management endpoints
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/{code}', [AssetController::class, 'show']);
    
    // Exchange rate endpoints
    Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
    Route::get('/exchange-rates/{from}/{to}', [ExchangeRateController::class, 'show']);
    Route::get('/exchange-rates/{from}/{to}/convert', [ExchangeRateController::class, 'convert']);
});

// Include BIAN-compliant routes
require __DIR__.'/api-bian.php';

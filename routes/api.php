<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountBalanceController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\CustodianController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\VoteController;
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

    // Governance endpoints
    Route::prefix('polls')->group(function () {
        Route::get('/', [PollController::class, 'index']);
        Route::get('/active', [PollController::class, 'active']);
        Route::post('/', [PollController::class, 'store']);
        Route::get('/{uuid}', [PollController::class, 'show']);
        Route::post('/{uuid}/activate', [PollController::class, 'activate']);
        Route::post('/{uuid}/vote', [PollController::class, 'vote']);
        Route::get('/{uuid}/results', [PollController::class, 'results']);
        Route::get('/{uuid}/voting-power', [PollController::class, 'votingPower']);
    });

    Route::prefix('votes')->group(function () {
        Route::get('/', [VoteController::class, 'index']);
        Route::get('/stats', [VoteController::class, 'stats']);
        Route::get('/{id}', [VoteController::class, 'show']);
        Route::post('/{id}/verify', [VoteController::class, 'verify']);
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
    
    // Exchange rate provider endpoints
    Route::prefix('exchange-providers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'index']);
        Route::get('/{provider}/rate', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'getRate']);
        Route::get('/compare', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'compareRates']);
        Route::get('/aggregated', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'getAggregatedRate']);
        Route::post('/refresh', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'refresh'])->middleware('auth:sanctum');
        Route::get('/historical', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'historical']);
        Route::post('/validate', [\App\Http\Controllers\Api\ExchangeRateProviderController::class, 'validate']);
    });
});

// Include BIAN-compliant routes
require __DIR__.'/api-bian.php';

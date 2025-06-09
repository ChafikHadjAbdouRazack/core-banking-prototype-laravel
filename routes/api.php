<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BalanceController;
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

    // Balance inquiry endpoints
    Route::get('/accounts/{uuid}/balance', [BalanceController::class, 'show']);
    Route::get('/accounts/{uuid}/balance/summary', [BalanceController::class, 'summary']);
});

// Include BIAN-compliant routes
require __DIR__.'/api-bian.php';

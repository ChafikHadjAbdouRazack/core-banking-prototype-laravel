<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BankAlertingController;
use App\Http\Controllers\Api\BankAllocationController;
use App\Http\Controllers\Api\BatchProcessingController;
use App\Http\Controllers\Api\DailyReconciliationController;
use App\Http\Controllers\Api\RegulatoryReportingController;
use App\Http\Controllers\Api\WorkflowMonitoringController;
use Illuminate\Support\Facades\Route;

// Banking operations endpoints
Route::middleware('auth:sanctum', 'check.token.expiration')->group(function () {
    // Batch Processing endpoints
    Route::prefix('batch-operations')->group(function () {
        Route::post('/execute', [BatchProcessingController::class, 'executeBatch']);
        Route::get('/{batchId}/status', [BatchProcessingController::class, 'getBatchStatus']);
        Route::get('/', [BatchProcessingController::class, 'getBatchHistory']);
        Route::post('/{batchId}/cancel', [BatchProcessingController::class, 'cancelBatch']);
    });

    // Bank Allocation endpoints
    Route::prefix('bank-allocations')->group(function () {
        Route::get('/', [BankAllocationController::class, 'index']);
        Route::put('/', [BankAllocationController::class, 'update']);
        Route::post('/banks', [BankAllocationController::class, 'addBank']);
        Route::delete('/banks/{bankCode}', [BankAllocationController::class, 'removeBank']);
        Route::put('/primary/{bankCode}', [BankAllocationController::class, 'setPrimaryBank']);
        Route::get('/available-banks', [BankAllocationController::class, 'getAvailableBanks']);
        Route::post('/distribution-preview', [BankAllocationController::class, 'previewDistribution']);
    });

    // Regulatory Reporting endpoints (admin only)
    Route::prefix('regulatory')->group(function () {
        Route::post('/reports/ctr', [RegulatoryReportingController::class, 'generateCTR']);
        Route::post('/reports/sar-candidates', [RegulatoryReportingController::class, 'generateSARCandidates']);
        Route::post('/reports/compliance-summary', [RegulatoryReportingController::class, 'generateComplianceSummary']);
        Route::post('/reports/kyc', [RegulatoryReportingController::class, 'generateKycReport']);
        Route::get('/reports', [RegulatoryReportingController::class, 'listReports']);
        Route::get('/reports/{filename}', [RegulatoryReportingController::class, 'getReport']);
        Route::get('/reports/{filename}/download', [RegulatoryReportingController::class, 'downloadReport'])->name('api.regulatory.download');
        Route::delete('/reports/{filename}', [RegulatoryReportingController::class, 'deleteReport']);
        Route::get('/metrics', [RegulatoryReportingController::class, 'getMetrics']);
    });

    // Daily Reconciliation endpoints (admin only)
    Route::prefix('reconciliation')->group(function () {
        Route::post('/trigger', [DailyReconciliationController::class, 'triggerReconciliation']);
        Route::get('/latest', [DailyReconciliationController::class, 'getLatestReport']);
        Route::get('/history', [DailyReconciliationController::class, 'getHistory']);
        Route::get('/reports/{date}', [DailyReconciliationController::class, 'getReportByDate']);
        Route::get('/metrics', [DailyReconciliationController::class, 'getMetrics']);
        Route::get('/status', [DailyReconciliationController::class, 'getStatus']);
    });

    // Bank Health & Alerting endpoints (admin only)
    Route::prefix('bank-health')->group(function () {
        Route::post('/check', [BankAlertingController::class, 'triggerHealthCheck']);
        Route::get('/status', [BankAlertingController::class, 'getHealthStatus']);
        Route::get('/custodians/{custodian}', [BankAlertingController::class, 'getCustodianHealth']);
        Route::get('/alerts/{custodian}/history', [BankAlertingController::class, 'getAlertHistory']);
        Route::get('/alerts/stats', [BankAlertingController::class, 'getAlertingStats']);
        Route::put('/alerts/config', [BankAlertingController::class, 'configureAlerts']);
        Route::get('/alerts/config', [BankAlertingController::class, 'getAlertConfiguration']);
        Route::post('/alerts/test', [BankAlertingController::class, 'testAlert']);
        Route::post('/alerts/{alertId}/acknowledge', [BankAlertingController::class, 'acknowledgeAlert']);
    });

    // Workflow/Saga Monitoring endpoints (admin only)
    Route::prefix('workflows')->middleware('api.rate_limit:admin')->group(function () {
        Route::get('/', [WorkflowMonitoringController::class, 'index']);
        Route::get('/stats', [WorkflowMonitoringController::class, 'stats']);
        Route::get('/metrics', [WorkflowMonitoringController::class, 'metrics']);
        Route::get('/search', [WorkflowMonitoringController::class, 'search']);
        Route::get('/status/{status}', [WorkflowMonitoringController::class, 'byStatus']);
        Route::get('/failed', [WorkflowMonitoringController::class, 'failed']);
        Route::get('/compensations', [WorkflowMonitoringController::class, 'compensations']);
        Route::get('/{id}', [WorkflowMonitoringController::class, 'show']);
    });
});

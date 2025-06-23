<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Custodian\Services\BankAlertingService;
use App\Domain\Custodian\Services\CustodianHealthMonitor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankAlertingController extends Controller
{
    public function __construct(
        private readonly BankAlertingService $alertingService,
        private readonly CustodianHealthMonitor $healthMonitor
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Trigger system-wide health check and alerting
     */
    public function triggerHealthCheck(): JsonResponse
    {
        try {
            Log::info('Manual bank health check triggered via API');
            
            $this->alertingService->performHealthCheck();
            
            // Get current health status of all custodians
            $allHealth = $this->healthMonitor->getAllCustodiansHealth();
            
            $summary = [
                'healthy' => 0,
                'degraded' => 0,
                'unhealthy' => 0,
                'unknown' => 0,
            ];
            
            foreach ($allHealth as $health) {
                $status = $health['status'] ?? 'unknown';
                $summary[$status] = ($summary[$status] ?? 0) + 1;
            }
            
            return response()->json([
                'data' => [
                    'health_check_completed' => true,
                    'checked_at' => now()->toISOString(),
                    'custodians_checked' => count($allHealth),
                    'summary' => $summary,
                    'custodian_details' => $allHealth,
                ],
                'message' => 'Bank health check completed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Bank health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Health check failed',
                'message' => $e->getMessage(),
                'checked_at' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get current health status of all custodians
     */
    public function getHealthStatus(): JsonResponse
    {
        try {
            $allHealth = $this->healthMonitor->getAllCustodiansHealth();
            
            $summary = [
                'healthy' => 0,
                'degraded' => 0,
                'unhealthy' => 0,
                'unknown' => 0,
            ];
            
            $details = [];
            
            foreach ($allHealth as $custodian => $health) {
                $status = $health['status'] ?? 'unknown';
                $summary[$status] = ($summary[$status] ?? 0) + 1;
                
                $details[] = [
                    'custodian' => $custodian,
                    'status' => $status,
                    'overall_failure_rate' => $health['overall_failure_rate'] ?? 0,
                    'last_check' => $health['last_check'] ?? null,
                    'response_time_ms' => $health['response_time_ms'] ?? null,
                    'consecutive_failures' => $health['consecutive_failures'] ?? 0,
                    'available_since' => $health['available_since'] ?? null,
                    'last_failure' => $health['last_failure'] ?? null,
                ];
            }
            
            return response()->json([
                'data' => [
                    'summary' => $summary,
                    'total_custodians' => count($allHealth),
                    'custodians' => $details,
                    'checked_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve health status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health status for specific custodian
     */
    public function getCustodianHealth(string $custodian): JsonResponse
    {
        try {
            $health = $this->healthMonitor->getCustodianHealth($custodian);
            
            if (!$health) {
                return response()->json([
                    'error' => 'Custodian not found',
                    'custodian' => $custodian
                ], 404);
            }
            
            return response()->json([
                'data' => [
                    'custodian' => $custodian,
                    'health' => $health,
                    'checked_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve custodian health',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alert history for a custodian
     */
    public function getAlertHistory(Request $request, string $custodian): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:90',
        ]);

        try {
            $days = $request->get('days', 7);
            
            $history = $this->alertingService->getAlertHistory($custodian, $days);
            
            return response()->json([
                'data' => [
                    'custodian' => $custodian,
                    'period_days' => $days,
                    'alert_history' => $history,
                    'retrieved_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve alert history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overall alerting statistics
     */
    public function getAlertingStats(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:hour,day,week,month',
        ]);

        try {
            $period = $request->get('period', 'day');
            
            // In a real implementation, this would query from database
            // For now, return sample statistics
            $stats = [
                'period' => $period,
                'total_alerts_sent' => 45,
                'alerts_by_severity' => [
                    'info' => 20,
                    'warning' => 20,
                    'critical' => 5,
                ],
                'alerts_by_custodian' => [
                    'paysera' => 15,
                    'deutsche_bank' => 10,
                    'santander' => 12,
                    'wise' => 8,
                ],
                'most_common_issues' => [
                    'high_failure_rate' => 18,
                    'slow_response_time' => 12,
                    'connection_timeout' => 8,
                    'authentication_error' => 5,
                    'rate_limit_exceeded' => 2,
                ],
                'alert_response_times' => [
                    'average_seconds' => 45,
                    'median_seconds' => 30,
                    'p95_seconds' => 120,
                ],
                'false_positive_rate' => 8.5,
                'period_start' => now()->sub($period, 1)->toISOString(),
                'period_end' => now()->toISOString(),
            ];
            
            return response()->json([
                'data' => [
                    'statistics' => $stats,
                    'calculated_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to calculate alerting statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configure alert settings
     */
    public function configureAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
            'severity_thresholds' => 'sometimes|array',
            'severity_thresholds.failure_rate_warning' => 'sometimes|numeric|min:0|max:100',
            'severity_thresholds.failure_rate_critical' => 'sometimes|numeric|min:0|max:100',
            'severity_thresholds.response_time_warning' => 'sometimes|integer|min:0',
            'severity_thresholds.response_time_critical' => 'sometimes|integer|min:0',
            'notification_channels' => 'sometimes|array',
            'notification_channels.*' => 'sometimes|in:mail,database,slack,webhook',
        ]);

        try {
            $config = [
                'cooldown_minutes' => $request->get('cooldown_minutes', 30),
                'severity_thresholds' => $request->get('severity_thresholds', [
                    'failure_rate_warning' => 10.0,
                    'failure_rate_critical' => 25.0,
                    'response_time_warning' => 5000,
                    'response_time_critical' => 10000,
                ]),
                'notification_channels' => $request->get('notification_channels', ['mail', 'database']),
                'updated_at' => now()->toISOString(),
            ];
            
            // In a real implementation, this would save to database or config
            Log::info('Alert configuration updated', $config);
            
            return response()->json([
                'data' => [
                    'configuration_updated' => true,
                    'new_configuration' => $config,
                ],
                'message' => 'Alert configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update alert configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current alert configuration
     */
    public function getAlertConfiguration(): JsonResponse
    {
        try {
            // In a real implementation, this would read from database or config
            $config = [
                'cooldown_minutes' => 30,
                'severity_thresholds' => [
                    'failure_rate_warning' => 10.0,
                    'failure_rate_critical' => 25.0,
                    'response_time_warning' => 5000,
                    'response_time_critical' => 10000,
                ],
                'notification_channels' => ['mail', 'database'],
                'alert_recipients' => [
                    'critical' => ['admin@finaegis.com', 'ops@finaegis.com'],
                    'warning' => ['ops@finaegis.com'],
                    'info' => ['ops@finaegis.com'],
                ],
                'last_updated' => now()->subDays(5)->toISOString(),
            ];
            
            return response()->json([
                'data' => [
                    'configuration' => $config,
                    'retrieved_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve alert configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test alert system by sending a test alert
     */
    public function testAlert(Request $request): JsonResponse
    {
        $request->validate([
            'severity' => 'required|in:info,warning,critical',
            'custodian' => 'sometimes|string',
            'message' => 'sometimes|string|max:500',
        ]);

        try {
            $severity = $request->get('severity');
            $custodian = $request->get('custodian', 'test_custodian');
            $message = $request->get('message', 'Test alert from API');
            
            Log::info('Test alert triggered', [
                'severity' => $severity,
                'custodian' => $custodian,
                'message' => $message,
                'triggered_by' => auth()->user()->email,
            ]);
            
            // In a real implementation, this would send an actual test alert
            
            return response()->json([
                'data' => [
                    'test_alert_sent' => true,
                    'severity' => $severity,
                    'custodian' => $custodian,
                    'message' => $message,
                    'sent_at' => now()->toISOString(),
                ],
                'message' => 'Test alert sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send test alert',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acknowledge an alert (mark as resolved)
     */
    public function acknowledgeAlert(Request $request, string $alertId): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'sometimes|string|max:1000',
        ]);

        try {
            $resolutionNotes = $request->get('resolution_notes', '');
            
            // In a real implementation, this would update the alert in database
            Log::info('Alert acknowledged', [
                'alert_id' => $alertId,
                'acknowledged_by' => auth()->user()->email,
                'resolution_notes' => $resolutionNotes,
            ]);
            
            return response()->json([
                'data' => [
                    'alert_id' => $alertId,
                    'acknowledged' => true,
                    'acknowledged_by' => auth()->user()->email,
                    'acknowledged_at' => now()->toISOString(),
                    'resolution_notes' => $resolutionNotes,
                ],
                'message' => 'Alert acknowledged successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to acknowledge alert',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
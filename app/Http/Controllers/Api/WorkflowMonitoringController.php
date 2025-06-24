<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Workflow\Models\StoredWorkflow;
use Workflow\Models\StoredWorkflowLog;
use Workflow\Models\StoredWorkflowException;

class WorkflowMonitoringController extends Controller
{
    /**
     * Get all workflows with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|string|in:created,pending,running,completed,failed,waiting',
            'class' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date|after_or_equal:created_from'
        ]);

        $query = StoredWorkflow::query()
            ->with(['logs:id,stored_workflow_id,index,class,result,created_at'])
            ->select([
                'id', 'class', 'status', 'arguments', 
                'output', 'created_at', 'updated_at'
            ]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('class')) {
            $query->where('class', 'LIKE', '%' . $request->class . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('class', 'LIKE', "%{$search}%")
                  ->orWhere('arguments', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to . ' 23:59:59');
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        $workflows = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $workflows->items(),
            'meta' => [
                'current_page' => $workflows->currentPage(),
                'last_page' => $workflows->lastPage(),
                'per_page' => $workflows->perPage(),
                'total' => $workflows->total(),
                'from' => $workflows->firstItem(),
                'to' => $workflows->lastItem(),
            ],
            'stats' => $this->getWorkflowStats()
        ]);
    }

    /**
     * Get specific workflow details with full logs
     */
    public function show(string $id): JsonResponse
    {
        $workflow = StoredWorkflow::with([
            'logs' => function($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->findOrFail($id);

        // Get exceptions for this workflow
        $exceptions = StoredWorkflowException::where('stored_workflow_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'workflow' => $workflow,
            'exceptions' => $exceptions,
            'compensation_info' => $this->getCompensationInfo($workflow),
            'execution_timeline' => $this->buildExecutionTimeline($workflow)
        ]);
    }

    /**
     * Get workflow statistics dashboard data
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getWorkflowStats());
    }

    /**
     * Get workflows by status
     */
    public function byStatus(string $status): JsonResponse
    {
        $validStatuses = ['created', 'pending', 'running', 'completed', 'failed', 'waiting'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status'], 400);
        }

        $workflows = StoredWorkflow::where('status', $status)
            ->with(['logs:id,stored_workflow_id,index,class,result,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => $status,
            'count' => $workflows->total(),
            'data' => $workflows->items(),
            'meta' => [
                'current_page' => $workflows->currentPage(),
                'last_page' => $workflows->lastPage(),
                'per_page' => $workflows->perPage(),
                'total' => $workflows->total(),
            ]
        ]);
    }

    /**
     * Get failed workflows with detailed error information
     */
    public function failed(): JsonResponse
    {
        $failedWorkflows = StoredWorkflow::where('status', 'failed')
            ->with(['logs'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $workflowIds = $failedWorkflows->pluck('id');
        $exceptions = StoredWorkflowException::whereIn('stored_workflow_id', $workflowIds)
            ->get()
            ->groupBy('stored_workflow_id');

        $enrichedWorkflows = $failedWorkflows->getCollection()->map(function($workflow) use ($exceptions) {
            $workflow->exceptions = $exceptions->get($workflow->id, collect());
            return $workflow;
        });

        return response()->json([
            'data' => $enrichedWorkflows,
            'meta' => [
                'current_page' => $failedWorkflows->currentPage(),
                'last_page' => $failedWorkflows->lastPage(),
                'per_page' => $failedWorkflows->perPage(),
                'total' => $failedWorkflows->total(),
            ],
            'error_summary' => $this->getErrorSummary()
        ]);
    }

    /**
     * Get workflow execution metrics
     */
    public function metrics(): JsonResponse
    {
        $now = now();
        $last24Hours = $now->copy()->subDay();
        $last7Days = $now->copy()->subWeek();

        return response()->json([
            'execution_metrics' => [
                'last_24_hours' => [
                    'total_executions' => StoredWorkflow::where('created_at', '>=', $last24Hours)->count(),
                    'successful' => StoredWorkflow::where('created_at', '>=', $last24Hours)->where('status', 'completed')->count(),
                    'failed' => StoredWorkflow::where('created_at', '>=', $last24Hours)->where('status', 'failed')->count(),
                    'average_duration' => $this->getAverageDuration($last24Hours)
                ],
                'last_7_days' => [
                    'total_executions' => StoredWorkflow::where('created_at', '>=', $last7Days)->count(),
                    'successful' => StoredWorkflow::where('created_at', '>=', $last7Days)->where('status', 'completed')->count(),
                    'failed' => StoredWorkflow::where('created_at', '>=', $last7Days)->where('status', 'failed')->count(),
                    'average_duration' => $this->getAverageDuration($last7Days)
                ]
            ],
            'workflow_types' => $this->getWorkflowTypeMetrics(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ]);
    }

    /**
     * Search workflows by various criteria
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'type' => 'sometimes|string|in:class,arguments,output,exception',
            'per_page' => 'sometimes|integer|min:1|max:50'
        ]);

        $query = StoredWorkflow::query();
        $searchTerm = $request->get('query');
        $searchType = $request->get('type', 'all');

        if ($searchType === 'class' || $searchType === 'all') {
            $query->orWhere('class', 'LIKE', "%{$searchTerm}%");
        }

        if ($searchType === 'arguments' || $searchType === 'all') {
            $query->orWhere('arguments', 'LIKE', "%{$searchTerm}%");
        }

        if ($searchType === 'output' || $searchType === 'all') {
            $query->orWhere('output', 'LIKE', "%{$searchTerm}%");
        }

        if ($searchType === 'exception' || $searchType === 'all') {
            $query->orWhere('exception', 'LIKE', "%{$searchTerm}%");
        }

        $results = $query->with(['logs:id,stored_workflow_id,index,class,result,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'search_query' => $searchTerm,
            'search_type' => $searchType,
            'results' => $results->items(),
            'meta' => [
                'total_found' => $results->total(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }

    /**
     * Get compensation tracking information
     */
    public function compensations(): JsonResponse
    {
        // Find workflows that have compensation patterns
        $compensatedWorkflows = StoredWorkflow::whereNotNull('exception')
            ->orWhere('status', 'failed')
            ->with(['logs'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $compensationData = $compensatedWorkflows->getCollection()->map(function($workflow) {
            return [
                'workflow' => $workflow,
                'compensation_info' => $this->getCompensationInfo($workflow),
                'rollback_activities' => $this->getRollbackActivities($workflow)
            ];
        });

        return response()->json([
            'data' => $compensationData,
            'meta' => [
                'current_page' => $compensatedWorkflows->currentPage(),
                'last_page' => $compensatedWorkflows->lastPage(),
                'total' => $compensatedWorkflows->total(),
            ],
            'compensation_summary' => $this->getCompensationSummary()
        ]);
    }

    // Private helper methods

    private function getWorkflowStats(): array
    {
        return [
            'total_workflows' => StoredWorkflow::count(),
            'by_status' => StoredWorkflow::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'recent_executions' => StoredWorkflow::where('created_at', '>=', now()->subHour())->count(),
            'avg_execution_time' => $this->getAverageDuration(now()->subWeek())
        ];
    }

    private function getCompensationInfo($workflow): array
    {
        // Look for compensation-related log entries
        $compensationLogs = $workflow->logs->filter(function($log) {
            $result = json_decode($log->result, true);
            $resultMessage = is_array($result) ? ($result['message'] ?? '') : '';
            
            return stripos($log->class, 'compensation') !== false ||
                   stripos($log->class, 'rollback') !== false ||
                   stripos($resultMessage, 'compensation') !== false ||
                   stripos($resultMessage, 'rollback') !== false;
        });

        return [
            'has_compensation' => $compensationLogs->isNotEmpty(),
            'compensation_logs' => $compensationLogs->values(),
            'compensation_count' => $compensationLogs->count()
        ];
    }

    private function buildExecutionTimeline($workflow): array
    {
        $timeline = [];
        
        foreach ($workflow->logs as $log) {
            $result = json_decode($log->result, true);
            $timeline[] = [
                'timestamp' => $log->created_at,
                'index' => $log->index,
                'class' => $log->class,
                'result' => $result
            ];
        }

        return $timeline;
    }

    private function getErrorSummary(): array
    {
        $exceptions = StoredWorkflowException::selectRaw('class, COUNT(*) as count')
            ->groupBy('class')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'most_common_errors' => $exceptions->toArray(),
            'total_exceptions' => StoredWorkflowException::count(),
            'recent_exceptions' => StoredWorkflowException::where('created_at', '>=', now()->subDay())->count()
        ];
    }

    private function getAverageDuration($since): ?float
    {
        $workflows = StoredWorkflow::where('created_at', '>=', $since)
            ->whereNotNull('updated_at')
            ->get(['created_at', 'updated_at']);

        if ($workflows->isEmpty()) {
            return null;
        }

        $totalDuration = $workflows->sum(function($workflow) {
            return $workflow->updated_at->diffInSeconds($workflow->created_at);
        });

        return round($totalDuration / $workflows->count(), 2);
    }

    private function getWorkflowTypeMetrics(): array
    {
        // Use database-agnostic date functions
        $dbDriver = config('database.default');
        
        if ($dbDriver === 'sqlite') {
            $durationSql = 'AVG((julianday(updated_at) - julianday(created_at)) * 86400)';
        } else {
            $durationSql = 'AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at))';
        }
        
        return StoredWorkflow::selectRaw("class, COUNT(*) as executions, {$durationSql} as avg_duration_seconds")
            ->whereNotNull('updated_at')
            ->groupBy('class')
            ->orderBy('executions', 'desc')
            ->limit(15)
            ->get()
            ->toArray();
    }

    private function getPerformanceMetrics(): array
    {
        $dbDriver = config('database.default');
        
        if ($dbDriver === 'sqlite') {
            $durationSql = '(julianday(updated_at) - julianday(created_at)) * 86400';
            $avgDurationSql = 'AVG((julianday(updated_at) - julianday(created_at)) * 86400)';
        } else {
            $durationSql = 'TIMESTAMPDIFF(SECOND, created_at, updated_at)';
            $avgDurationSql = 'AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at))';
        }
        
        $slowWorkflows = StoredWorkflow::selectRaw("id, class, {$durationSql} as duration_seconds")
            ->whereNotNull('updated_at')
            ->whereRaw("({$durationSql}) > 30")
            ->orderByRaw("{$durationSql} desc")
            ->limit(10)
            ->get();

        return [
            'slow_workflows' => $slowWorkflows->toArray(),
            'average_duration_by_status' => StoredWorkflow::selectRaw("status, {$avgDurationSql} as avg_duration")
                ->whereNotNull('updated_at')
                ->groupBy('status')
                ->pluck('avg_duration', 'status')
                ->toArray()
        ];
    }

    private function getRollbackActivities($workflow): array
    {
        // Look for rollback/compensation activities in logs
        return $workflow->logs->filter(function($log) {
            $className = strtolower($log->class);
            $result = json_decode($log->result, true);
            $resultMessage = is_array($result) ? strtolower($result['message'] ?? '') : '';
            
            return strpos($className, 'rollback') !== false ||
                   strpos($className, 'compensation') !== false ||
                   strpos($className, 'undo') !== false ||
                   strpos($className, 'reverse') !== false ||
                   strpos($resultMessage, 'rollback') !== false ||
                   strpos($resultMessage, 'compensation') !== false ||
                   strpos($resultMessage, 'undo') !== false ||
                   strpos($resultMessage, 'reverse') !== false;
        })->values()->toArray();
    }

    private function getCompensationSummary(): array
    {
        $totalWorkflows = StoredWorkflow::count();
        $failedWorkflows = StoredWorkflow::where('status', 'failed')->count();
        
        return [
            'total_workflows' => $totalWorkflows,
            'failed_workflows' => $failedWorkflows,
            'failure_rate' => $totalWorkflows > 0 ? round(($failedWorkflows / $totalWorkflows) * 100, 2) : 0,
            'compensations_triggered' => StoredWorkflowLog::where('class', 'LIKE', '%compensation%')->count()
        ];
    }
}
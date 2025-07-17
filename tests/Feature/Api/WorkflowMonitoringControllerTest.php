<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Workflow\Models\StoredWorkflow;
use Workflow\Models\StoredWorkflowException;
use Workflow\Models\StoredWorkflowLog;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

describe('Workflow Monitoring API', function () {

    test('can list workflows with filtering', function () {
        // Create test workflows
        $completedWorkflow = StoredWorkflow::create([
            'class'     => 'App\Domain\Payment\Workflows\TransferWorkflow',
            'status'    => 'completed',
            'arguments' => json_encode(['amount' => 100]),
            'output'    => json_encode(['success' => true]),
        ]);

        $failedWorkflow = StoredWorkflow::create([
            'class'     => 'App\Domain\Basket\Workflows\ComposeBasketWorkflow',
            'status'    => 'failed',
            'arguments' => json_encode(['basketCode' => 'TECH']),
        ]);

        $response = $this->getJson('/api/workflows');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'class',
                        'status',
                        'created_at',
                        'logs',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                ],
                'stats' => [
                    'total_workflows',
                    'by_status',
                    'recent_executions',
                ],
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    test('can filter workflows by status', function () {
        StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'completed',
            'arguments' => '{}',
        ]);

        StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'failed',
            'arguments' => '{}',
        ]);

        $response = $this->getJson('/api/workflows?status=completed');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe('completed');
    });

    test('can search workflows by class name', function () {
        StoredWorkflow::create([
            'class'     => 'App\Domain\Payment\Workflows\TransferWorkflow',
            'status'    => 'completed',
            'arguments' => '{}',
        ]);

        StoredWorkflow::create([
            'class'     => 'App\Domain\Basket\Workflows\ComposeBasketWorkflow',
            'status'    => 'completed',
            'arguments' => '{}',
        ]);

        $response = $this->getJson('/api/workflows?search=Transfer');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.class'))->toContain('TransferWorkflow');
    });

    test('can get specific workflow details', function () {
        $workflow = StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'completed',
            'arguments' => json_encode(['test' => 'data']),
            'output'    => json_encode(['result' => 'success']),
        ]);

        // Add some logs
        StoredWorkflowLog::create([
            'stored_workflow_id' => $workflow->id,
            'index'              => 0,
            'class'              => 'TestActivity',
            'result'             => json_encode(['message' => 'Workflow started']),
            'now'                => now(),
        ]);

        $response = $this->getJson("/api/workflows/{$workflow->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'workflow' => [
                    'id',
                    'class',
                    'status',
                    'arguments',
                    'output',
                    'logs',
                ],
                'exceptions',
                'compensation_info' => [
                    'has_compensation',
                    'compensation_logs',
                    'compensation_count',
                ],
                'execution_timeline',
            ]);

        expect($response->json('workflow.id'))->toBe($workflow->id);
        expect($response->json('workflow.logs'))->toHaveCount(1);
    });

    test('can get workflow statistics', function () {
        // Create workflows with different statuses
        StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'completed',
            'arguments' => '{}',
        ]);

        StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'failed',
            'arguments' => '{}',
        ]);

        $response = $this->getJson('/api/workflows/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_workflows',
                'by_status',
                'recent_executions',
                'avg_execution_time',
            ]);

        expect($response->json('total_workflows'))->toBe(2);
        expect($response->json('by_status'))->toHaveKey('completed');
        expect($response->json('by_status'))->toHaveKey('failed');
    });

    test('can get workflows by status endpoint', function () {
        StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'running',
            'arguments' => '{}',
        ]);

        $response = $this->getJson('/api/workflows/status/running');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'count',
                'data',
                'meta',
            ]);

        expect($response->json('status'))->toBe('running');
        expect($response->json('count'))->toBe(1);
    });

    test('validates status parameter in by-status endpoint', function () {
        $response = $this->getJson('/api/workflows/status/invalid-status');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid status']);
    });

    test('can get failed workflows with exceptions', function () {
        $failedWorkflow = StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'failed',
            'arguments' => '{}',
        ]);

        // Add exception record
        StoredWorkflowException::create([
            'stored_workflow_id' => $failedWorkflow->id,
            'class'              => 'TestException',
            'exception'          => json_encode(['message' => 'Test error occurred', 'trace' => 'Stack trace here']),
        ]);

        $response = $this->getJson('/api/workflows/failed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'exceptions',
                    ],
                ],
                'error_summary' => [
                    'most_common_errors',
                    'total_exceptions',
                    'recent_exceptions',
                ],
            ]);

        expect($response->json('data.0.status'))->toBe('failed');
        expect($response->json('data.0.exceptions'))->toHaveCount(1);
    });

    test('can get workflow execution metrics', function () {
        // Create workflows in different time periods
        StoredWorkflow::create([
            'class'      => 'TestWorkflow',
            'status'     => 'completed',
            'arguments'  => '{}',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2)->addMinutes(5),
        ]);

        $response = $this->getJson('/api/workflows/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'execution_metrics' => [
                    'last_24_hours' => [
                        'total_executions',
                        'successful',
                        'failed',
                        'average_duration',
                    ],
                    'last_7_days' => [
                        'total_executions',
                        'successful',
                        'failed',
                        'average_duration',
                    ],
                ],
                'workflow_types',
                'performance_metrics',
            ]);
    });

    test('can search workflows with different criteria', function () {
        StoredWorkflow::create([
            'class'     => 'PaymentWorkflow',
            'status'    => 'completed',
            'arguments' => json_encode(['amount' => 500]),
        ]);

        $response = $this->getJson('/api/workflows/search?query=Payment&type=class');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'search_query',
                'search_type',
                'results',
                'meta' => [
                    'total_found',
                    'current_page',
                ],
            ]);

        expect($response->json('search_query'))->toBe('Payment');
        expect($response->json('search_type'))->toBe('class');
        expect($response->json('results'))->toHaveCount(1);
    });

    test('validates search parameters', function () {
        $response = $this->getJson('/api/workflows/search?query=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    test('can get compensation tracking information', function () {
        // Create workflow with compensation logs
        $workflow = StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'failed',
            'arguments' => '{}',
        ]);

        StoredWorkflowLog::create([
            'stored_workflow_id' => $workflow->id,
            'index'              => 0,
            'class'              => 'CompensationActivity',
            'result'             => json_encode(['message' => 'Running compensation logic']),
            'now'                => now(),
        ]);

        $response = $this->getJson('/api/workflows/compensations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'workflow',
                        'compensation_info' => [
                            'has_compensation',
                            'compensation_logs',
                            'compensation_count',
                        ],
                        'rollback_activities',
                    ],
                ],
                'compensation_summary' => [
                    'total_workflows',
                    'failed_workflows',
                    'failure_rate',
                    'compensations_triggered',
                ],
            ]);

        expect($response->json('data.0.compensation_info.has_compensation'))->toBeTrue();
        expect($response->json('data.0.compensation_info.compensation_count'))->toBe(1);
    });

    test('all endpoints work with authentication', function () {
        $endpoints = [
            '/api/workflows',
            '/api/workflows/stats',
            '/api/workflows/metrics',
            '/api/workflows/search?query=test',
            '/api/workflows/status/completed',
            '/api/workflows/failed',
            '/api/workflows/compensations',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertSuccessful(); // 2xx status codes
        }
    });

    test('handles pagination correctly', function () {
        // Create multiple workflows
        for ($i = 0; $i < 25; $i++) {
            StoredWorkflow::create([
                'class'     => "TestWorkflow{$i}",
                'status'    => 'completed',
                'arguments' => '{}',
            ]);
        }

        $response = $this->getJson('/api/workflows?per_page=10');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(10);
        expect($response->json('meta.total'))->toBe(25);
        expect($response->json('meta.last_page'))->toBe(3);
    });

    test('handles date filtering correctly', function () {
        $oldWorkflow = StoredWorkflow::create([
            'class'      => 'OldWorkflow',
            'status'     => 'completed',
            'arguments'  => '{}',
            'created_at' => now()->subWeek(),
        ]);

        $newWorkflow = StoredWorkflow::create([
            'class'      => 'NewWorkflow',
            'status'     => 'completed',
            'arguments'  => '{}',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/workflows?created_from=' . now()->subDay()->toDateString());

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.class'))->toBe('NewWorkflow');
    });

    test('handles empty results gracefully', function () {
        $response = $this->getJson('/api/workflows?status=completed');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(0);
        expect($response->json('meta.total'))->toBe(0);
    });

    test('workflow details endpoint handles missing workflow', function () {
        $response = $this->getJson('/api/workflows/99999');

        $response->assertStatus(404);
    });

    test('compensation tracking identifies rollback activities', function () {
        $workflow = StoredWorkflow::create([
            'class'     => 'TestWorkflow',
            'status'    => 'failed',
            'arguments' => '{}',
        ]);

        // Add various rollback-related logs
        $rollbackTypes = ['rollback', 'compensation', 'undo', 'reverse'];
        foreach ($rollbackTypes as $index => $type) {
            StoredWorkflowLog::create([
                'stored_workflow_id' => $workflow->id,
                'index'              => $index,
                'class'              => ucfirst($type) . 'Activity',
                'result'             => json_encode(['message' => "Executing {$type} operation"]),
                'now'                => now(),
            ]);
        }

        $response = $this->getJson('/api/workflows/compensations');

        $response->assertStatus(200);
        $compensationData = $response->json('data.0');

        expect($compensationData['compensation_info']['has_compensation'])->toBeTrue();
        expect($compensationData['rollback_activities'])->toHaveCount(4);
    });
});

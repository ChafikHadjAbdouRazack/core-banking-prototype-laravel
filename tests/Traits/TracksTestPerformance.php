<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Log;

trait TracksTestPerformance
{
    /**
     * Track slow test threshold in seconds.
     */
    protected float $slowTestThreshold = 1.0;

    /**
     * Start time of the test.
     */
    protected float $testStartTime;

    /**
     * Set up performance tracking.
     */
    protected function setUpPerformanceTracking(): void
    {
        $this->testStartTime = microtime(true);
    }

    /**
     * Track test performance after completion.
     */
    protected function trackTestPerformance(): void
    {
        $executionTime = microtime(true) - $this->testStartTime;
        $testName = $this->getName();

        // Log slow tests
        if ($executionTime > $this->slowTestThreshold) {
            $this->logSlowTest($testName, $executionTime);
        }

        // Store metrics for reporting
        $this->storeTestMetrics($testName, $executionTime);
    }

    /**
     * Log slow test execution.
     */
    protected function logSlowTest(string $testName, float $executionTime): void
    {
        $message = sprintf(
            'Slow test detected: %s took %.2f seconds (threshold: %.2f seconds)',
            $testName,
            $executionTime,
            $this->slowTestThreshold
        );

        Log::warning($message);

        // Also write to a dedicated slow test log file
        $logFile = storage_path('logs/slow-tests.log');
        $logEntry = sprintf(
            "[%s] %s - %.2f seconds\n",
            date('Y-m-d H:i:s'),
            $testName,
            $executionTime
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Store test metrics for analysis.
     */
    protected function storeTestMetrics(string $testName, float $executionTime): void
    {
        $metricsFile = storage_path('logs/test-metrics.json');

        $metrics = [];
        if (file_exists($metricsFile)) {
            $content = file_get_contents($metricsFile);
            $metrics = json_decode($content, true) ?? [];
        }

        $metrics[] = [
            'test'           => $testName,
            'execution_time' => $executionTime,
            'timestamp'      => now()->toIso8601String(),
            'memory_peak'    => memory_get_peak_usage(true) / 1048576, // MB
        ];

        // Keep only last 1000 entries to prevent file from growing too large
        if (count($metrics) > 1000) {
            $metrics = array_slice($metrics, -1000);
        }

        file_put_contents(
            $metricsFile,
            json_encode($metrics, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    /**
     * Get slow test threshold.
     */
    public function getSlowTestThreshold(): float
    {
        return $this->slowTestThreshold;
    }

    /**
     * Set slow test threshold.
     */
    public function setSlowTestThreshold(float $threshold): void
    {
        $this->slowTestThreshold = $threshold;
    }
}

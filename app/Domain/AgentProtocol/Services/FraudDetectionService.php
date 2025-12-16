<?php

declare(strict_types=1);

namespace App\Domain\AgentProtocol\Services;

use App\Domain\AgentProtocol\Models\Agent;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for detecting fraudulent transactions using configurable risk analysis.
 *
 * Configuration is loaded from config/agent_protocol.php:
 * - fraud_detection.risk_thresholds: Score thresholds for risk levels (low, medium, high, critical)
 * - fraud_detection.risk_weights: Weights for each fraud detection pattern
 * - fraud_detection.velocity_limits: Transaction count limits (hourly, daily)
 * - fraud_detection.pattern_scores: Points assigned for detected fraud patterns
 * - fraud_detection.suspicious_hours: Night time hours considered suspicious
 * - fraud_detection.structuring_threshold: Amount threshold for round amount detection
 * - fraud_detection.structuring_count: Count threshold for structuring detection
 * - fraud_detection.small_transaction_amount: Threshold for small transaction detection
 * - fraud_detection.cache_ttl: Cache duration for fraud analysis results
 */
class FraudDetectionService
{
    /**
     * Get risk thresholds from configuration.
     *
     * @return array<string, float>
     */
    private function getRiskThresholds(): array
    {
        $thresholds = config('agent_protocol.fraud_detection.risk_thresholds', []);

        return [
            'low'      => (float) ($thresholds['low'] ?? 30.0),
            'medium'   => (float) ($thresholds['medium'] ?? 60.0),
            'high'     => (float) ($thresholds['high'] ?? 80.0),
            'critical' => (float) ($thresholds['critical'] ?? 95.0),
        ];
    }

    /**
     * Get fraud pattern weights from configuration.
     *
     * Weights are normalized to decimals (e.g., 25 -> 0.25).
     *
     * @return array<string, array{weight: float, enabled: bool}>
     */
    private function getFraudPatterns(): array
    {
        $weights = config('agent_protocol.fraud_detection.risk_weights', []);
        $enabled = config('agent_protocol.fraud_detection.enabled', true);

        return [
            'velocity_check'     => ['weight' => ((float) ($weights['velocity'] ?? 25)) / 100, 'enabled' => $enabled],
            'amount_anomaly'     => ['weight' => ((float) ($weights['amount_anomaly'] ?? 20)) / 100, 'enabled' => $enabled],
            'reputation_check'   => ['weight' => ((float) ($weights['reputation'] ?? 15)) / 100, 'enabled' => $enabled],
            'pattern_matching'   => ['weight' => ((float) ($weights['pattern'] ?? 20)) / 100, 'enabled' => $enabled],
            'geographic_anomaly' => ['weight' => ((float) ($weights['geographic'] ?? 10)) / 100, 'enabled' => $enabled],
            'time_anomaly'       => ['weight' => ((float) ($weights['time_of_day'] ?? 10)) / 100, 'enabled' => $enabled],
        ];
    }

    /**
     * Get velocity limits from configuration.
     *
     * @return array<string, int>
     */
    private function getVelocityLimits(): array
    {
        $limits = config('agent_protocol.fraud_detection.velocity_limits', []);

        return [
            'hourly' => (int) ($limits['hourly_count'] ?? 10),
            'daily'  => (int) ($limits['daily_count'] ?? 50),
        ];
    }

    /**
     * Get pattern scores from configuration.
     *
     * @return array<string, int>
     */
    private function getPatternScores(): array
    {
        $scores = config('agent_protocol.fraud_detection.pattern_scores', []);

        return [
            'round_amount'  => (int) ($scores['round_amount'] ?? 20),
            'structuring'   => (int) ($scores['structuring'] ?? 40),
            'sequential'    => (int) ($scores['sequential'] ?? 30),
            'suspicious_ua' => (int) ($scores['suspicious_ua'] ?? 25),
        ];
    }

    /**
     * Get suspicious hours range from configuration.
     *
     * @return array<string, int>
     */
    private function getSuspiciousHours(): array
    {
        $hours = config('agent_protocol.fraud_detection.suspicious_hours', []);

        return [
            'start' => (int) ($hours['start'] ?? 2),
            'end'   => (int) ($hours['end'] ?? 5),
        ];
    }

    /**
     * Get structuring threshold from configuration.
     */
    private function getStructuringThreshold(): float
    {
        return (float) config('agent_protocol.fraud_detection.structuring_threshold', 1000);
    }

    /**
     * Get structuring count threshold from configuration.
     */
    private function getStructuringCount(): int
    {
        return (int) config('agent_protocol.fraud_detection.structuring_count', 5);
    }

    /**
     * Get small transaction amount threshold from configuration.
     */
    private function getSmallTransactionAmount(): float
    {
        return (float) config('agent_protocol.fraud_detection.small_transaction_amount', 100);
    }

    /**
     * Get cache TTL from configuration.
     */
    private function getCacheTtl(): int
    {
        return (int) config('agent_protocol.fraud_detection.cache_ttl', 2592000);
    }

    /**
     * Analyze transaction for fraud risk.
     *
     * @param string $transactionId Unique transaction identifier
     * @param string $agentId Agent's DID
     * @param float $amount Transaction amount
     * @param array<string, mixed> $metadata Additional transaction metadata
     * @return array<string, mixed> Analysis results including risk score, level, and decision
     */
    public function analyzeTransaction(
        string $transactionId,
        string $agentId,
        float $amount,
        array $metadata = []
    ): array {
        $riskFactors = [];
        $totalRiskScore = 0.0;
        $fraudPatterns = $this->getFraudPatterns();
        $riskThresholds = $this->getRiskThresholds();

        // Check velocity (transaction frequency)
        if ($fraudPatterns['velocity_check']['enabled']) {
            $velocityRisk = $this->checkVelocity($agentId, $amount);
            $riskFactors['velocity'] = $velocityRisk;
            $totalRiskScore += $velocityRisk['score'] * $fraudPatterns['velocity_check']['weight'];
        }

        // Check amount anomaly
        if ($fraudPatterns['amount_anomaly']['enabled']) {
            $amountRisk = $this->checkAmountAnomaly($agentId, $amount);
            $riskFactors['amount'] = $amountRisk;
            $totalRiskScore += $amountRisk['score'] * $fraudPatterns['amount_anomaly']['weight'];
        }

        // Check agent reputation
        if ($fraudPatterns['reputation_check']['enabled']) {
            $reputationRisk = $this->checkReputation($agentId);
            $riskFactors['reputation'] = $reputationRisk;
            $totalRiskScore += $reputationRisk['score'] * $fraudPatterns['reputation_check']['weight'];
        }

        // Pattern matching
        if ($fraudPatterns['pattern_matching']['enabled']) {
            $patternRisk = $this->checkPatterns($agentId, $amount, $metadata);
            $riskFactors['patterns'] = $patternRisk;
            $totalRiskScore += $patternRisk['score'] * $fraudPatterns['pattern_matching']['weight'];
        }

        // Geographic anomaly
        if ($fraudPatterns['geographic_anomaly']['enabled'] && isset($metadata['location'])) {
            $geoRisk = $this->checkGeographicAnomaly($agentId, $metadata['location']);
            $riskFactors['geographic'] = $geoRisk;
            $totalRiskScore += $geoRisk['score'] * $fraudPatterns['geographic_anomaly']['weight'];
        }

        // Time-based anomaly
        if ($fraudPatterns['time_anomaly']['enabled']) {
            $timeRisk = $this->checkTimeAnomaly($agentId);
            $riskFactors['time'] = $timeRisk;
            $totalRiskScore += $timeRisk['score'] * $fraudPatterns['time_anomaly']['weight'];
        }

        $decision = $this->makeDecision($totalRiskScore);

        $analysis = [
            'transaction_id'  => $transactionId,
            'agent_id'        => $agentId,
            'risk_score'      => round($totalRiskScore, 2),
            'risk_level'      => $this->getRiskLevel($totalRiskScore),
            'risk_factors'    => $riskFactors,
            'decision'        => $decision,
            'requires_review' => $decision === 'review',
            'timestamp'       => now()->toIso8601String(),
        ];

        // Cache the analysis
        $this->cacheAnalysis($transactionId, $analysis);

        // Log high-risk transactions
        if ($totalRiskScore >= $riskThresholds['high']) {
            Log::warning('High-risk transaction detected', $analysis);
        }

        return $analysis;
    }

    /**
     * Check transaction velocity against configured limits.
     *
     * @param string $agentId Agent's DID
     * @param float $amount Transaction amount (unused but kept for consistency)
     * @return array<string, mixed> Velocity risk assessment
     */
    private function checkVelocity(string $agentId, float $amount): array
    {
        $recentTransactions = $this->getRecentTransactionCount($agentId, 60); // Last hour
        $dailyTransactions = $this->getRecentTransactionCount($agentId, 1440); // Last 24 hours

        $velocityLimits = $this->getVelocityLimits();
        $hourlyLimit = $velocityLimits['hourly'];
        $dailyLimit = $velocityLimits['daily'];

        $hourlyRisk = min(100, ($recentTransactions / $hourlyLimit) * 100);
        $dailyRisk = min(100, ($dailyTransactions / $dailyLimit) * 100);

        $velocityScore = max($hourlyRisk, $dailyRisk * 0.7);

        return [
            'score'          => $velocityScore,
            'hourly_count'   => $recentTransactions,
            'daily_count'    => $dailyTransactions,
            'hourly_risk'    => $hourlyRisk,
            'daily_risk'     => $dailyRisk,
            'exceeds_limits' => $recentTransactions > $hourlyLimit || $dailyTransactions > $dailyLimit,
        ];
    }

    /**
     * Check for amount anomalies.
     */
    private function checkAmountAnomaly(string $agentId, float $amount): array
    {
        $stats = $this->getTransactionStats($agentId);

        if ($stats['count'] === 0) {
            // New agent, moderate risk
            return [
                'score'      => 50.0,
                'is_anomaly' => false,
                'reason'     => 'new_agent',
            ];
        }

        $mean = $stats['avg'];
        $stdDev = $stats['std_dev'];

        // Calculate z-score
        $zScore = $stdDev > 0 ? abs($amount - $mean) / $stdDev : 0;

        // Risk increases with deviation from normal
        $anomalyScore = min(100, $zScore * 20);

        return [
            'score'       => $anomalyScore,
            'is_anomaly'  => $zScore > 3,
            'z_score'     => round($zScore, 2),
            'mean_amount' => round($mean, 2),
            'std_dev'     => round($stdDev, 2),
            'amount'      => $amount,
        ];
    }

    /**
     * Check agent reputation.
     */
    private function checkReputation(string $agentId): array
    {
        try {
            // For now, get reputation from Agent model directly to avoid event sourcing issues
            $agent = Agent::where('agent_id', $agentId)->first();

            if (! $agent) {
                // Unknown agent, high risk
                return [
                    'score'            => 80.0,
                    'reputation_score' => 0,
                    'trust_level'      => 'unknown',
                    'is_trusted'       => false,
                ];
            }

            $reputationScore = $agent->reputation_score ?? 50.0;

            // Invert reputation score for risk (low reputation = high risk)
            $riskScore = 100 - $reputationScore;

            $trustLevel = match (true) {
                $reputationScore >= 90 => 'excellent',
                $reputationScore >= 75 => 'good',
                $reputationScore >= 50 => 'neutral',
                $reputationScore >= 25 => 'low',
                default                => 'poor',
            };

            return [
                'score'            => $riskScore,
                'reputation_score' => $reputationScore,
                'trust_level'      => $trustLevel,
                'is_trusted'       => $reputationScore >= 70,
            ];
        } catch (Exception $e) {
            // Unknown agent, high risk
            return [
                'score'            => 80.0,
                'reputation_score' => 0,
                'trust_level'      => 'unknown',
                'is_trusted'       => false,
            ];
        }
    }

    /**
     * Check for known fraud patterns using configured thresholds and scores.
     *
     * @param string $agentId Agent's DID
     * @param float $amount Transaction amount
     * @param array<string, mixed> $metadata Transaction metadata
     * @return array<string, mixed> Pattern detection results
     */
    private function checkPatterns(string $agentId, float $amount, array $metadata): array
    {
        $patterns = [];
        $patternScore = 0;
        $patternScores = $this->getPatternScores();
        $structuringThreshold = $this->getStructuringThreshold();
        $structuringCount = $this->getStructuringCount();

        // Round amount fraud (amounts ending in .00)
        if ($amount == floor($amount) && $amount > $structuringThreshold) {
            $patterns[] = 'round_amount';
            $patternScore += $patternScores['round_amount'];
        }

        // Rapid small transactions (structuring)
        $smallTxCount = $this->getSmallTransactionCount($agentId, 60);
        if ($smallTxCount > $structuringCount) {
            $patterns[] = 'structuring';
            $patternScore += $patternScores['structuring'];
        }

        // Sequential transaction amounts
        if ($this->hasSequentialAmounts($agentId)) {
            $patterns[] = 'sequential_amounts';
            $patternScore += $patternScores['sequential'];
        }

        // Known fraud indicators in metadata
        if (isset($metadata['user_agent']) && $this->isSuspiciousUserAgent($metadata['user_agent'])) {
            $patterns[] = 'suspicious_user_agent';
            $patternScore += $patternScores['suspicious_ua'];
        }

        return [
            'score'             => min(100, $patternScore),
            'patterns_detected' => $patterns,
            'pattern_count'     => count($patterns),
        ];
    }

    /**
     * Check for geographic anomalies.
     */
    private function checkGeographicAnomaly(string $agentId, array $location): array
    {
        $previousLocations = $this->getRecentLocations($agentId);

        if (empty($previousLocations)) {
            return [
                'score'      => 0,
                'is_anomaly' => false,
                'reason'     => 'no_history',
            ];
        }

        $currentCountry = $location['country'] ?? 'unknown';
        $isNewCountry = ! in_array($currentCountry, $previousLocations);

        // Check for impossible travel
        $impossibleTravel = $this->checkImpossibleTravel($agentId, $location);

        $geoScore = 0;
        if ($isNewCountry) {
            $geoScore += 40;
        }
        if ($impossibleTravel) {
            $geoScore += 60;
        }

        return [
            'score'             => min(100, $geoScore),
            'is_anomaly'        => $isNewCountry || $impossibleTravel,
            'new_country'       => $isNewCountry,
            'impossible_travel' => $impossibleTravel,
            'country'           => $currentCountry,
        ];
    }

    /**
     * Check for time-based anomalies using configured suspicious hours.
     *
     * @param string $agentId Agent's DID
     * @return array<string, mixed> Time-based risk assessment
     */
    private function checkTimeAnomaly(string $agentId): array
    {
        $currentHour = now()->hour;
        $isWeekend = now()->isWeekend();
        $suspiciousHours = $this->getSuspiciousHours();

        $typicalHours = $this->getTypicalActivityHours($agentId);

        $isUnusualTime = ! in_array($currentHour, $typicalHours);
        $isNightTime = $currentHour >= $suspiciousHours['start'] && $currentHour <= $suspiciousHours['end'];

        $timeScore = 0;
        if ($isUnusualTime) {
            $timeScore += 30;
        }
        if ($isNightTime) {
            $timeScore += 20;
        }
        if ($isWeekend && ! $this->hasWeekendActivity($agentId)) {
            $timeScore += 15;
        }

        return [
            'score'         => min(100, $timeScore),
            'is_anomaly'    => $isUnusualTime || $isNightTime,
            'current_hour'  => $currentHour,
            'is_weekend'    => $isWeekend,
            'is_night_time' => $isNightTime,
            'typical_hours' => $typicalHours,
        ];
    }

    /**
     * Make a decision based on risk score using configured thresholds.
     *
     * @param float $riskScore Calculated risk score
     * @return string Decision: 'approve', 'review', or 'reject'
     */
    private function makeDecision(float $riskScore): string
    {
        $thresholds = $this->getRiskThresholds();

        if ($riskScore < $thresholds['low']) {
            return 'approve';
        } elseif ($riskScore < $thresholds['high']) {
            return 'review';
        } else {
            return 'reject';
        }
    }

    /**
     * Get risk level from score using configured thresholds.
     *
     * @param float $riskScore Calculated risk score
     * @return string Risk level: 'low', 'medium', 'high', or 'critical'
     */
    private function getRiskLevel(float $riskScore): string
    {
        $thresholds = $this->getRiskThresholds();

        return match (true) {
            $riskScore >= $thresholds['critical'] => 'critical',
            $riskScore >= $thresholds['high']     => 'high',
            $riskScore >= $thresholds['medium']   => 'medium',
            default                               => 'low',
        };
    }

    // Helper methods
    private function getRecentTransactionCount(string $agentId, int $minutes): int
    {
        return DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }

    private function getTransactionStats(string $agentId): array
    {
        $transactions = DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->pluck('amount')
            ->toArray();

        $count = count($transactions);
        $avg = $count > 0 ? array_sum($transactions) / $count : 0;

        // Calculate standard deviation manually for database compatibility
        $stdDev = 0;
        if ($count > 1) {
            $variance = 0;
            foreach ($transactions as $amount) {
                $variance += pow($amount - $avg, 2);
            }
            $variance /= ($count - 1);
            $stdDev = sqrt($variance);
        }

        return [
            'count'   => $count,
            'avg'     => $avg,
            'std_dev' => $stdDev,
        ];
    }

    /**
     * Get count of small transactions for structuring detection.
     *
     * @param string $agentId Agent's DID
     * @param int $minutes Time window in minutes
     * @return int Count of small transactions
     */
    private function getSmallTransactionCount(string $agentId, int $minutes): int
    {
        $smallTxAmount = $this->getSmallTransactionAmount();

        return DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->where('amount', '<', $smallTxAmount)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }

    private function hasSequentialAmounts(string $agentId): bool
    {
        $recentAmounts = DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->where('created_at', '>', now()->subHours(1))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('amount')
            ->toArray();

        if (count($recentAmounts) < 3) {
            return false;
        }

        // Check for arithmetic sequence
        $differences = [];
        for ($i = 1; $i < count($recentAmounts); $i++) {
            $differences[] = $recentAmounts[$i] - $recentAmounts[$i - 1];
        }

        $uniqueDiffs = array_unique($differences);

        return count($uniqueDiffs) === 1 && $uniqueDiffs[0] != 0;
    }

    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
        ];

        $userAgentLower = strtolower($userAgent);
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getRecentLocations(string $agentId): array
    {
        return DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->where('created_at', '>', now()->subDays(30))
            ->whereNotNull('metadata->country')
            ->distinct('metadata->country')
            ->pluck('metadata->country')
            ->toArray();
    }

    private function checkImpossibleTravel(string $agentId, array $location): bool
    {
        // Simplified check - in production, calculate actual distance and time
        $lastTransaction = DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->where('created_at', '>', now()->subHours(2))
            ->whereNotNull('metadata->country')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastTransaction) {
            return false;
        }

        // If countries are different and less than 2 hours apart
        $lastCountry = json_decode($lastTransaction->metadata, true)['country'] ?? null;

        return $lastCountry !== ($location['country'] ?? null);
    }

    private function getTypicalActivityHours(string $agentId): array
    {
        $transactions = DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->get();

        $hourCounts = [];
        foreach ($transactions as $transaction) {
            $hour = (int) date('H', strtotime($transaction->created_at));
            if (! isset($hourCounts[$hour])) {
                $hourCounts[$hour] = 0;
            }
            $hourCounts[$hour]++;
        }

        $hours = [];
        foreach ($hourCounts as $hour => $count) {
            if ($count > 2) {
                $hours[] = $hour;
            }
        }

        return $hours ?: range(9, 17); // Default business hours
    }

    private function hasWeekendActivity(string $agentId): bool
    {
        $transactions = DB::table('agent_transactions')
            ->where('from_agent_id', $agentId)
            ->get();

        foreach ($transactions as $transaction) {
            $dayOfWeek = date('w', strtotime($transaction->created_at));
            // 0 = Sunday, 6 = Saturday in PHP's date('w')
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cache fraud analysis results using configured TTL.
     *
     * @param string $transactionId Transaction identifier
     * @param array<string, mixed> $analysis Analysis results
     */
    private function cacheAnalysis(string $transactionId, array $analysis): void
    {
        $cacheTtl = $this->getCacheTtl();

        Cache::put(
            "fraud_analysis:{$transactionId}",
            $analysis,
            now()->addSeconds($cacheTtl)
        );
    }
}

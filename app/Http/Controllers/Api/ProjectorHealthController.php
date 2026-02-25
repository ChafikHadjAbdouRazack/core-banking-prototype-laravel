<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Monitoring\Services\ProjectorHealthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProjectorHealthController extends Controller
{
    public function __construct(
        private readonly ProjectorHealthService $healthService
    ) {
    }

    /**
     * Get projector health status.
     *
     * @OA\Get(
     *     path="/api/monitoring/projector-health",
     *     summary="Get projector health status",
     *     tags={"Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Projector health status",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_projectors", type="integer"),
     *             @OA\Property(property="healthy", type="integer"),
     *             @OA\Property(property="stale", type="integer"),
     *             @OA\Property(property="failed", type="integer"),
     *             @OA\Property(property="checked_at", type="string", format="date-time")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $status = $this->healthService->getCachedStatus();

        return response()->json($status);
    }

    /**
     * Get stale projectors only.
     *
     * @OA\Get(
     *     path="/api/monitoring/projector-health/stale",
     *     summary="Get stale projectors",
     *     tags={"Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of stale projectors"
     *     )
     * )
     */
    public function stale(): JsonResponse
    {
        $staleProjectors = $this->healthService->detectStaleProjectors();

        return response()->json([
            'stale_projectors' => $staleProjectors,
            'count'            => $staleProjectors->count(),
        ]);
    }
}

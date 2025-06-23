<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Compliance\Services\RegulatoryReportingService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegulatoryReportingController extends Controller
{
    public function __construct(
        private readonly RegulatoryReportingService $regulatoryReportingService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['getReport', 'listReports']);
    }

    /**
     * Generate Currency Transaction Report (CTR)
     */
    public function generateCTR(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
        ]);

        try {
            $date = Carbon::parse($request->date);
            $filename = $this->regulatoryReportingService->generateCTR($date);
            
            return response()->json([
                'data' => [
                    'type' => 'ctr',
                    'date' => $date->toDateString(),
                    'filename' => $filename,
                    'generated_at' => now()->toISOString(),
                    'download_url' => route('api.regulatory.download', ['filename' => basename($filename)]),
                ],
                'message' => 'Currency Transaction Report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate CTR report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Suspicious Activity Report (SAR) candidates
     */
    public function generateSARCandidates(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            $filename = $this->regulatoryReportingService->generateSARCandidates($startDate, $endDate);
            
            return response()->json([
                'data' => [
                    'type' => 'sar_candidates',
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString(),
                    'filename' => $filename,
                    'generated_at' => now()->toISOString(),
                    'download_url' => route('api.regulatory.download', ['filename' => basename($filename)]),
                ],
                'message' => 'SAR candidates report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate SAR candidates report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate compliance summary report
     */
    public function generateComplianceSummary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m|before_or_equal:' . now()->format('Y-m'),
        ]);

        try {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $filename = $this->regulatoryReportingService->generateComplianceSummary($month);
            
            return response()->json([
                'data' => [
                    'type' => 'compliance_summary',
                    'month' => $month->format('F Y'),
                    'filename' => $filename,
                    'generated_at' => now()->toISOString(),
                    'download_url' => route('api.regulatory.download', ['filename' => basename($filename)]),
                ],
                'message' => 'Compliance summary report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate compliance summary report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate KYC compliance report
     */
    public function generateKycReport(): JsonResponse
    {
        try {
            $filename = $this->regulatoryReportingService->generateKycReport();
            
            return response()->json([
                'data' => [
                    'type' => 'kyc_compliance',
                    'filename' => $filename,
                    'generated_at' => now()->toISOString(),
                    'download_url' => route('api.regulatory.download', ['filename' => basename($filename)]),
                ],
                'message' => 'KYC compliance report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate KYC compliance report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all available regulatory reports
     */
    public function listReports(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|in:ctr,sar,compliance,kyc',
            'limit' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        try {
            $type = $request->get('type');
            $limit = $request->get('limit', 20);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;

            $directories = [
                'ctr' => 'regulatory/ctr/',
                'sar' => 'regulatory/sar/',
                'compliance' => 'regulatory/compliance/',
                'kyc' => 'regulatory/kyc/',
            ];

            $reports = collect();

            $searchDirs = $type ? [$type => $directories[$type]] : $directories;

            foreach ($searchDirs as $reportType => $directory) {
                $files = Storage::files($directory);
                
                foreach ($files as $file) {
                    $reports->push([
                        'type' => $reportType,
                        'filename' => basename($file),
                        'full_path' => $file,
                        'size' => Storage::size($file),
                        'created_at' => Carbon::createFromTimestamp(Storage::lastModified($file))->toISOString(),
                        'download_url' => route('api.regulatory.download', ['filename' => basename($file)]),
                    ]);
                }
            }

            // Sort by creation date (newest first)
            $reports = $reports->sortByDesc('created_at');
            
            $total = $reports->count();
            $paginatedReports = $reports->slice($offset, $limit)->values();

            return response()->json([
                'data' => [
                    'reports' => $paginatedReports,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $limit,
                        'current_page' => $page,
                        'last_page' => ceil($total / $limit),
                        'has_more' => $page < ceil($total / $limit),
                    ],
                ],
                'meta' => [
                    'available_types' => array_keys($directories),
                    'total_reports' => $total,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to list regulatory reports',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific report content
     */
    public function getReport(Request $request, string $filename): JsonResponse
    {
        try {
            // Security: Only allow specific file extensions and patterns
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $filename)) {
                return response()->json([
                    'error' => 'Invalid filename format'
                ], 400);
            }

            // Search for the file in all regulatory directories
            $directories = [
                'regulatory/ctr/',
                'regulatory/sar/',
                'regulatory/compliance/',
                'regulatory/kyc/',
            ];

            $filePath = null;
            foreach ($directories as $directory) {
                $possiblePath = $directory . $filename;
                if (Storage::exists($possiblePath)) {
                    $filePath = $possiblePath;
                    break;
                }
            }

            if (!$filePath) {
                return response()->json([
                    'error' => 'Report not found'
                ], 404);
            }

            $content = Storage::get($filePath);
            $reportData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid report format'
                ], 500);
            }

            return response()->json([
                'data' => [
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'size' => Storage::size($filePath),
                    'created_at' => Carbon::createFromTimestamp(Storage::lastModified($filePath))->toISOString(),
                    'content' => $reportData,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download report file
     */
    public function downloadReport(string $filename)
    {
        try {
            // Security: Only allow specific file extensions and patterns
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $filename)) {
                return response()->json([
                    'error' => 'Invalid filename format'
                ], 400);
            }

            // Search for the file in all regulatory directories
            $directories = [
                'regulatory/ctr/',
                'regulatory/sar/',
                'regulatory/compliance/',
                'regulatory/kyc/',
            ];

            $filePath = null;
            foreach ($directories as $directory) {
                $possiblePath = $directory . $filename;
                if (Storage::exists($possiblePath)) {
                    $filePath = $possiblePath;
                    break;
                }
            }

            if (!$filePath) {
                return response()->json([
                    'error' => 'Report not found'
                ], 404);
            }

            return Storage::download($filePath, $filename, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a regulatory report
     */
    public function deleteReport(string $filename): JsonResponse
    {
        try {
            // Security: Only allow specific file extensions and patterns
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $filename)) {
                return response()->json([
                    'error' => 'Invalid filename format'
                ], 400);
            }

            // Search for the file in all regulatory directories
            $directories = [
                'regulatory/ctr/',
                'regulatory/sar/',
                'regulatory/compliance/',
                'regulatory/kyc/',
            ];

            $filePath = null;
            foreach ($directories as $directory) {
                $possiblePath = $directory . $filename;
                if (Storage::exists($possiblePath)) {
                    $filePath = $possiblePath;
                    break;
                }
            }

            if (!$filePath) {
                return response()->json([
                    'error' => 'Report not found'
                ], 404);
            }

            Storage::delete($filePath);

            return response()->json([
                'data' => [
                    'filename' => $filename,
                    'deleted_at' => now()->toISOString(),
                ],
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete report',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get regulatory metrics summary
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:week,month,quarter,year',
        ]);

        try {
            $period = $request->get('period', 'month');
            
            $endDate = now();
            $startDate = match($period) {
                'week' => $endDate->copy()->subWeek(),
                'month' => $endDate->copy()->subMonth(),
                'quarter' => $endDate->copy()->subQuarter(),
                'year' => $endDate->copy()->subYear(),
                default => $endDate->copy()->subMonth(),
            };

            // Get summary metrics using reflection to access protected methods
            $reflection = new \ReflectionClass($this->regulatoryReportingService);
            
            $kycMetrics = $reflection->getMethod('getKycMetrics');
            $kycMetrics->setAccessible(true);
            
            $transactionMetrics = $reflection->getMethod('getTransactionMetrics');
            $transactionMetrics->setAccessible(true);
            
            $userMetrics = $reflection->getMethod('getUserMetrics');
            $userMetrics->setAccessible(true);
            
            $riskMetrics = $reflection->getMethod('getRiskMetrics');
            $riskMetrics->setAccessible(true);
            
            $gdprMetrics = $reflection->getMethod('getGdprMetrics');
            $gdprMetrics->setAccessible(true);

            return response()->json([
                'data' => [
                    'period' => $period,
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString(),
                    'metrics' => [
                        'kyc' => $kycMetrics->invoke($this->regulatoryReportingService, $startDate, $endDate),
                        'transactions' => $transactionMetrics->invoke($this->regulatoryReportingService, $startDate, $endDate),
                        'users' => $userMetrics->invoke($this->regulatoryReportingService, $startDate, $endDate),
                        'risk' => $riskMetrics->invoke($this->regulatoryReportingService),
                        'gdpr' => $gdprMetrics->invoke($this->regulatoryReportingService, $startDate, $endDate),
                    ],
                    'generated_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve regulatory metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

declare(strict_types=1);

namespace Tests\Domain\Regulatory\Services;

use App\Domain\Regulatory\Models\RegulatoryReport;
use App\Domain\Regulatory\Services\ReportGeneratorService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReportGeneratorService.
 */
class ReportGeneratorServiceTest extends TestCase
{
    private ReportGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportGeneratorService();
    }

    /**
     * Helper to create a test double for RegulatoryReport.
     */
    private function createReportDouble(string $type, ?Carbon $periodEnd = null, ?string $reportId = null): RegulatoryReport
    {
        $endDate = $periodEnd ?? Carbon::now();
        $id = $reportId ?? 'RPT-' . uniqid();

        return new class($type, $endDate, $id) extends RegulatoryReport {
            public string $report_type;

            public Carbon $reporting_period_end;

            public string $report_id;

            public function __construct(string $type, Carbon $endDate, string $id)
            {
                // Skip parent constructor
                $this->report_type = $type;
                $this->reporting_period_end = $endDate;
                $this->report_id = $id;
            }

            public function __get($key)
            {
                return match ($key) {
                    'report_type' => $this->report_type,
                    'reporting_period_end' => $this->reporting_period_end,
                    'report_id' => $this->report_id,
                    default => null,
                };
            }
        };
    }

    public function test_extract_csv_headers_returns_ctr_headers(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_CTR);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractCsvHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service, $report);

        $this->assertContains('Transaction ID', $headers);
        $this->assertContains('Amount', $headers);
        $this->assertContains('Currency', $headers);
        $this->assertContains('Customer Name', $headers);
        $this->assertCount(15, $headers);
    }

    public function test_extract_csv_headers_returns_sar_headers(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_SAR);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractCsvHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service, $report);

        $this->assertContains('Activity ID', $headers);
        $this->assertContains('Risk Score', $headers);
        $this->assertContains('Suspicious Activity Description', $headers);
        $this->assertCount(11, $headers);
    }

    public function test_extract_csv_headers_returns_kyc_headers(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_KYC);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractCsvHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service, $report);

        $this->assertContains('Customer ID', $headers);
        $this->assertContains('KYC Status', $headers);
        $this->assertContains('Risk Rating', $headers);
        $this->assertContains('PEP Status', $headers);
        $this->assertCount(11, $headers);
    }

    public function test_extract_csv_headers_returns_default_headers_for_unknown_type(): void
    {
        $report = $this->createReportDouble('UNKNOWN_TYPE');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractCsvHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->service, $report);

        $this->assertContains('ID', $headers);
        $this->assertContains('Date', $headers);
        $this->assertContains('Type', $headers);
        $this->assertContains('Amount', $headers);
        $this->assertContains('Description', $headers);
        $this->assertCount(5, $headers);
    }

    public function test_get_certification_statement_for_ctr(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_CTR);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCertificationStatement');
        $method->setAccessible(true);

        $statement = $method->invoke($this->service, $report);

        $this->assertStringContainsString('Currency Transaction Report', $statement);
        $this->assertStringContainsString('complete and accurate', $statement);
    }

    public function test_get_certification_statement_for_sar(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_SAR);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCertificationStatement');
        $method->setAccessible(true);

        $statement = $method->invoke($this->service, $report);

        $this->assertStringContainsString('Suspicious Activity Report', $statement);
        $this->assertStringContainsString('all known information', $statement);
    }

    public function test_get_certification_statement_for_bsa(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_BSA);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCertificationStatement');
        $method->setAccessible(true);

        $statement = $method->invoke($this->service, $report);

        $this->assertStringContainsString('Bank Secrecy Act', $statement);
    }

    public function test_get_certification_statement_for_default(): void
    {
        $report = $this->createReportDouble('CUSTOM');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCertificationStatement');
        $method->setAccessible(true);

        $statement = $method->invoke($this->service, $report);

        $this->assertEquals('I certify that this report is complete and accurate.', $statement);
    }

    public function test_get_filename_includes_report_type(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_CTR, Carbon::parse('2026-01-25'));
        $report->report_id = 'RPT-12345';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->service, $report, 'json');

        $this->assertStringContainsString('regulatory/', $filename);
        $this->assertStringContainsString('ctr/', $filename);
        $this->assertStringContainsString('RPT-12345', $filename);
        $this->assertStringContainsString('2026_01_25', $filename);
        $this->assertStringEndsWith('.json', $filename);
    }

    public function test_get_filename_supports_different_extensions(): void
    {
        $report = $this->createReportDouble(RegulatoryReport::TYPE_SAR, Carbon::parse('2026-01-25'));
        $report->report_id = 'RPT-67890';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getFilename');
        $method->setAccessible(true);

        $csvFilename = $method->invoke($this->service, $report, 'csv');
        $xmlFilename = $method->invoke($this->service, $report, 'xml');
        $pdfFilename = $method->invoke($this->service, $report, 'pdf');

        $this->assertStringEndsWith('.csv', $csvFilename);
        $this->assertStringEndsWith('.xml', $xmlFilename);
        $this->assertStringEndsWith('.pdf', $pdfFilename);
    }

    public function test_array_to_xml_converts_simple_array(): void
    {
        $data = [
            'name'   => 'Test Report',
            'status' => 'pending',
            'count'  => 42,
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('arrayToXml');
        $method->setAccessible(true);

        $xml = new \SimpleXMLElement('<root/>');
        $method->invoke($this->service, $data, $xml);

        $this->assertEquals('Test Report', (string) $xml->name);
        $this->assertEquals('pending', (string) $xml->status);
        $this->assertEquals('42', (string) $xml->count);
    }

    public function test_array_to_xml_handles_nested_arrays(): void
    {
        $data = [
            'report'  => 'CTR',
            'details' => [
                'amount'   => 10000,
                'currency' => 'USD',
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('arrayToXml');
        $method->setAccessible(true);

        $xml = new \SimpleXMLElement('<root/>');
        $method->invoke($this->service, $data, $xml);

        $this->assertEquals('CTR', (string) $xml->report);
        $this->assertEquals('10000', (string) $xml->details->amount);
        $this->assertEquals('USD', (string) $xml->details->currency);
    }

    public function test_array_to_xml_handles_indexed_arrays(): void
    {
        $data = [
            'transactions' => [
                ['id' => 1, 'amount' => 100],
                ['id' => 2, 'amount' => 200],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('arrayToXml');
        $method->setAccessible(true);

        $xml = new \SimpleXMLElement('<root/>');
        $method->invoke($this->service, $data, $xml);

        // Indexed arrays are converted to item elements
        $this->assertNotEmpty($xml->transactions);
    }
}

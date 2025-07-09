<?php

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Services\PaymentService;
use Tests\TestCase;
use Workflow\WorkflowStub;

class PaymentServiceTest extends TestCase
{
    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        WorkflowStub::fake();
        $this->service = new PaymentService();
    }

    public function test_process_stripe_deposit_with_all_data(): void
    {
        $data = [
            'account_uuid' => 'acc-123',
            'amount' => 10000,
            'currency' => 'USD',
            'reference' => 'ref-456',
            'external_reference' => 'ext-789',
            'payment_method' => 'pm_1234567890',
            'payment_method_type' => 'card',
            'metadata' => [
                'customer_id' => 'cust-123',
                'description' => 'Test deposit',
            ],
        ];

        $result = $this->service->processStripeDeposit($data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('workflow-', $result);
    }

    public function test_process_stripe_deposit_without_metadata(): void
    {
        $data = [
            'account_uuid' => 'acc-123',
            'amount' => 5000,
            'currency' => 'EUR',
            'reference' => 'ref-456',
            'external_reference' => 'ext-789',
            'payment_method' => 'pm_9876543210',
            'payment_method_type' => 'sepa_debit',
        ];

        $result = $this->service->processStripeDeposit($data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('workflow-', $result);
    }

    public function test_process_bank_withdrawal_with_all_data(): void
    {
        $data = [
            'account_uuid' => 'acc-456',
            'amount' => 25000,
            'currency' => 'USD',
            'reference' => 'ref-789',
            'bank_name' => 'Chase Bank',
            'account_number' => '123456789',
            'account_holder_name' => 'John Doe',
            'routing_number' => '021000021',
            'iban' => 'US12345678901234567890',
            'swift' => 'CHASUS33',
            'metadata' => [
                'purpose' => 'Business expense',
                'approved_by' => 'admin-123',
            ],
        ];

        $result = $this->service->processBankWithdrawal($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('transfer_id', $result);
        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals($data['reference'], $result['reference']);
        $this->assertStringStartsWith('wtxn_', $result['transaction_id']);
        $this->assertStringStartsWith('transfer_', $result['transfer_id']);
    }

    public function test_process_bank_withdrawal_with_optional_fields_null(): void
    {
        $data = [
            'account_uuid' => 'acc-789',
            'amount' => 15000,
            'currency' => 'EUR',
            'reference' => 'ref-123',
            'bank_name' => 'Deutsche Bank',
            'account_number' => 'DE89370400440532013000',
            'account_holder_name' => 'Jane Smith',
        ];

        $result = $this->service->processBankWithdrawal($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('transfer_id', $result);
        $this->assertArrayHasKey('reference', $result);
    }

    public function test_process_stripe_deposit_with_large_amount(): void
    {
        $data = [
            'account_uuid' => 'acc-large',
            'amount' => 999999999, // Large amount
            'currency' => 'USD',
            'reference' => 'ref-large',
            'external_reference' => 'ext-large',
            'payment_method' => 'pm_large',
            'payment_method_type' => 'wire',
        ];

        $result = $this->service->processStripeDeposit($data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('workflow-', $result);
    }

    public function test_process_bank_withdrawal_with_different_currencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF'];

        foreach ($currencies as $currency) {
            $data = [
                'account_uuid' => 'acc-currency',
                'amount' => 10000,
                'currency' => $currency,
                'reference' => "ref-{$currency}",
                'bank_name' => 'International Bank',
                'account_number' => '987654321',
                'account_holder_name' => 'Currency Tester',
            ];

            $result = $this->service->processBankWithdrawal($data);

            $this->assertIsArray($result);
            $this->assertEquals("ref-{$currency}", $result['reference']);
        }
    }

    public function test_process_stripe_deposit_with_different_payment_method_types(): void
    {
        $paymentTypes = ['card', 'bank_transfer', 'sepa_debit', 'ach_credit_transfer', 'ideal'];

        foreach ($paymentTypes as $type) {
            $data = [
                'account_uuid' => 'acc-type',
                'amount' => 5000,
                'currency' => 'USD',
                'reference' => "ref-{$type}",
                'external_reference' => "ext-{$type}",
                'payment_method' => "pm_{$type}",
                'payment_method_type' => $type,
            ];

            $result = $this->service->processStripeDeposit($data);

            $this->assertIsString($result);
            $this->assertStringStartsWith('workflow-', $result);
        }
    }

    public function test_process_bank_withdrawal_with_complex_metadata(): void
    {
        $data = [
            'account_uuid' => 'acc-metadata',
            'amount' => 50000,
            'currency' => 'USD',
            'reference' => 'ref-metadata',
            'bank_name' => 'Wells Fargo',
            'account_number' => '1234567890',
            'account_holder_name' => 'Metadata Tester',
            'metadata' => [
                'transaction_type' => 'business',
                'department' => 'Finance',
                'cost_center' => 'CC-123',
                'approval_chain' => ['manager-1', 'director-2', 'cfo-3'],
                'notes' => 'Quarterly vendor payment',
                'invoice_numbers' => ['INV-001', 'INV-002', 'INV-003'],
            ],
        ];

        $result = $this->service->processBankWithdrawal($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('transfer_id', $result);
        $this->assertArrayHasKey('reference', $result);
    }

    public function test_process_stripe_deposit_with_zero_amount(): void
    {
        $data = [
            'account_uuid' => 'acc-zero',
            'amount' => 0,
            'currency' => 'USD',
            'reference' => 'ref-zero',
            'external_reference' => 'ext-zero',
            'payment_method' => 'pm_zero',
            'payment_method_type' => 'card',
        ];

        $result = $this->service->processStripeDeposit($data);

        $this->assertIsString($result);
        $this->assertStringStartsWith('workflow-', $result);
    }

    public function test_process_bank_withdrawal_with_minimal_data(): void
    {
        $data = [
            'account_uuid' => 'acc-min',
            'amount' => 100,
            'currency' => 'USD',
            'reference' => 'ref-min',
            'bank_name' => 'Test Bank',
            'account_number' => '12345',
            'account_holder_name' => 'Test User',
        ];

        $result = $this->service->processBankWithdrawal($data);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('ref-min', $result['reference']);
    }
}
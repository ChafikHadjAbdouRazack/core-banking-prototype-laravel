<?php

declare(strict_types=1);

namespace Tests\Domain\AI\Services;

use App\Domain\AI\Services\AIAgentService;
use PHPUnit\Framework\TestCase;
use Tests\Traits\InvokesPrivateMethods;

/**
 * Unit tests for AIAgentService.
 */
class AIAgentServiceTest extends TestCase
{
    use InvokesPrivateMethods;

    private AIAgentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AIAgentService();
    }

    // Chat method tests

    public function test_chat_returns_correct_structure(): void
    {
        $result = $this->service->chat(
            'Hello',
            'conv-123',
            1,
            [],
            []
        );

        $this->assertArrayHasKey('message_id', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('tools_used', $result);
        $this->assertArrayHasKey('context', $result);
    }

    public function test_chat_returns_uuid_message_id(): void
    {
        $result = $this->service->chat('test', 'conv-123', 1);

        // UUID format: 8-4-4-4-12
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $result['message_id']
        );
    }

    public function test_chat_returns_confidence_score(): void
    {
        $result = $this->service->chat('test', 'conv-123', 1);

        $this->assertEquals(0.85, $result['confidence']);
    }

    public function test_chat_returns_tools_used(): void
    {
        $result = $this->service->chat('test', 'conv-123', 1);

        $this->assertContains('AccountBalanceTool', $result['tools_used']);
        $this->assertContains('TransactionHistoryTool', $result['tools_used']);
    }

    public function test_chat_preserves_context(): void
    {
        $context = ['previous_intent' => 'balance_check'];

        $result = $this->service->chat('test', 'conv-123', 1, $context);

        $this->assertEquals($context, $result['context']);
    }

    // Demo response generation tests (via chat)

    public function test_chat_responds_to_balance_keyword(): void
    {
        $result = $this->service->chat('What is my balance?', 'conv-123', 1);

        $this->assertStringContainsString('balance', strtolower($result['content']));
        $this->assertStringContainsString('$12,456.78', $result['content']);
    }

    public function test_chat_responds_to_balance_keyword_case_insensitive(): void
    {
        $result = $this->service->chat('BALANCE check please', 'conv-123', 1);

        $this->assertStringContainsString('$12,456.78', $result['content']);
    }

    public function test_chat_responds_to_transaction_keyword(): void
    {
        $result = $this->service->chat('Show my transactions', 'conv-123', 1);

        $this->assertStringContainsString('transactions', strtolower($result['content']));
        $this->assertStringContainsString('Amazon', $result['content']);
    }

    public function test_chat_responds_to_transfer_keyword(): void
    {
        $result = $this->service->chat('I want to transfer money', 'conv-123', 1);

        $this->assertStringContainsString('transfer', strtolower($result['content']));
        $this->assertStringContainsString('recipient', strtolower($result['content']));
    }

    public function test_chat_responds_to_gcu_keyword(): void
    {
        $result = $this->service->chat('What is the GCU rate?', 'conv-123', 1);

        $this->assertStringContainsString('GCU', $result['content']);
        $this->assertStringContainsString('rate', strtolower($result['content']));
    }

    public function test_chat_responds_to_exchange_keyword(): void
    {
        $result = $this->service->chat('Exchange rate please', 'conv-123', 1);

        $this->assertStringContainsString('GCU', $result['content']);
    }

    public function test_chat_returns_default_response_for_unknown_query(): void
    {
        $result = $this->service->chat('Random question about weather', 'conv-123', 1);

        $this->assertStringContainsString('production environment', strtolower($result['content']));
    }

    // Private method tests via reflection

    public function test_generate_demo_response_balance(): void
    {
        $response = $this->invokeMethod($this->service, 'generateDemoResponse', ['check balance']);

        $this->assertStringContainsString('$12,456.78', $response);
    }

    public function test_generate_demo_response_transaction(): void
    {
        $response = $this->invokeMethod($this->service, 'generateDemoResponse', ['show transactions']);

        $this->assertStringContainsString('Amazon Purchase', $response);
        $this->assertStringContainsString('$156.32', $response);
    }

    public function test_generate_demo_response_transfer(): void
    {
        $response = $this->invokeMethod($this->service, 'generateDemoResponse', ['transfer funds']);

        $this->assertStringContainsString('transfer money', $response);
    }

    public function test_generate_demo_response_exchange(): void
    {
        $response = $this->invokeMethod($this->service, 'generateDemoResponse', ['exchange rate']);

        $this->assertStringContainsString('1 GCU = 1.00 USD', $response);
    }

    public function test_generate_demo_response_default(): void
    {
        $response = $this->invokeMethod($this->service, 'generateDemoResponse', ['hello world']);

        $this->assertStringContainsString('production environment', $response);
    }

    // storeFeedback tests

    public function test_store_feedback_does_not_throw(): void
    {
        // storeFeedback is a no-op, should not throw
        $this->service->storeFeedback('msg-123', 1, 5, 'Great response');

        $this->assertTrue(true); // No exception thrown
    }

    public function test_store_feedback_accepts_null_feedback(): void
    {
        $this->service->storeFeedback('msg-123', 1, 3, null);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_store_feedback_accepts_various_ratings(): void
    {
        foreach ([1, 2, 3, 4, 5] as $rating) {
            $this->service->storeFeedback('msg-' . $rating, 1, $rating);
        }

        $this->assertTrue(true); // No exception thrown
    }
}

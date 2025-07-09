<?php

namespace App\Jobs;

use App\Domain\Custodian\Services\WebhookProcessorService;
use App\Models\CustodianWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCustodianWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $webhookId
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookProcessorService $processor): void
    {
        $webhook = CustodianWebhook::where('uuid', $this->webhookId)->first();

        if (! $webhook) {
            Log::error('Webhook not found', ['webhook_id' => $this->webhookId]);

            return;
        }

        if ($webhook->status !== 'pending' && $webhook->status !== 'failed') {
            Log::info(
                'Webhook already processed', [
                'webhook_id' => $this->webhookId,
                'status'     => $webhook->status,
                ]
            );

            return;
        }

        try {
            $webhook->markAsProcessing();

            // Process the webhook based on custodian and event type
            $processor->process($webhook);

            $webhook->markAsProcessed();

            Log::info(
                'Webhook processed successfully', [
                'webhook_id' => $webhook->id,
                'custodian'  => $webhook->custodian_name,
                'event_type' => $webhook->event_type,
                ]
            );
        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());

            Log::error(
                'Failed to process webhook', [
                'webhook_id' => $webhook->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                ]
            );

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            'Webhook processing job failed permanently', [
            'webhook_id' => $this->webhookId,
            'error'      => $exception->getMessage(),
            ]
        );
    }
}

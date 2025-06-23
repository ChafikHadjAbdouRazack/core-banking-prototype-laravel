<x-guest-layout>
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        Webhooks
                    </h1>
                    <p class="mt-6 text-xl text-indigo-100 max-w-3xl mx-auto">
                        Real-time notifications for account events, transaction updates, and workflow completions.
                    </p>
                </div>
            </div>
        </div>

        <!-- Overview -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <section class="mb-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">How Webhooks Work</h2>
                    <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                        Webhooks enable real-time communication between FinAegis and your application. When events occur in our system, we'll send HTTP POST requests to your configured endpoints.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Event Occurs</h3>
                        <p class="text-gray-600">A transaction completes, account balance changes, or workflow finishes</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Webhook Sent</h3>
                        <p class="text-gray-600">FinAegis sends a secure HTTP POST request to your endpoint</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">You Respond</h3>
                        <p class="text-gray-600">Your application processes the event and returns HTTP 200</p>
                    </div>
                </div>
            </section>

            <!-- Event Types -->
            <section class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Supported Events</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Events</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">account.created</code>
                                    <span class="text-sm text-gray-600">New account opened</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">account.balance_updated</code>
                                    <span class="text-sm text-gray-600">Balance changed</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">account.frozen</code>
                                    <span class="text-sm text-gray-600">Account frozen by admin</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">account.unfrozen</code>
                                    <span class="text-sm text-gray-600">Account unfrozen</span>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Events</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">transfer.created</code>
                                    <span class="text-sm text-gray-600">Transfer initiated</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">transfer.pending</code>
                                    <span class="text-sm text-gray-600">Transfer in progress</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">transfer.completed</code>
                                    <span class="text-sm text-gray-600">Transfer successful</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">transfer.failed</code>
                                    <span class="text-sm text-gray-600">Transfer failed</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">transfer.reversed</code>
                                    <span class="text-sm text-gray-600">Transfer reversed</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Workflow Events</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">workflow.started</code>
                                    <span class="text-sm text-gray-600">Workflow initiated</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">workflow.step_completed</code>
                                    <span class="text-sm text-gray-600">Step finished</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">workflow.completed</code>
                                    <span class="text-sm text-gray-600">Workflow finished</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">workflow.failed</code>
                                    <span class="text-sm text-gray-600">Workflow failed</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">workflow.compensation_executed</code>
                                    <span class="text-sm text-gray-600">Rollback completed</span>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Events</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">system.maintenance_start</code>
                                    <span class="text-sm text-gray-600">Maintenance begins</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">system.maintenance_end</code>
                                    <span class="text-sm text-gray-600">Maintenance ends</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">system.rate_limit_warning</code>
                                    <span class="text-sm text-gray-600">Approaching rate limit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Setup Guide -->
            <section class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Setup Guide</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-semibold mb-4">1. Create Webhook Endpoint</h3>
                        <p class="text-gray-600 mb-4">Configure a webhook endpoint using the API:</p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-6">
                            <pre class="text-green-400 text-sm"><code>curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://yourapp.com/webhooks/finaegis",
    "events": [
      "transfer.completed",
      "transfer.failed", 
      "account.balance_updated"
    ],
    "secret": "your_webhook_secret",
    "active": true
  }' \
  https://api.finaegis.com/v1/webhooks</code></pre>
                        </div>

                        <h3 class="text-xl font-semibold mb-4">2. Handle Incoming Webhooks</h3>
                        <p class="text-gray-600 mb-4">Process webhook events in your application:</p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-green-400 text-sm"><code>app.post('/webhooks/finaegis', (req, res) => {
  const { event_type, data } = req.body;
  
  // Verify webhook signature
  if (!verifySignature(req)) {
    return res.status(401).send('Invalid signature');
  }
  
  switch (event_type) {
    case 'transfer.completed':
      handleTransferCompleted(data);
      break;
    case 'account.balance_updated':
      handleBalanceUpdate(data);
      break;
    default:
      console.log(`Unhandled event: ${event_type}`);
  }
  
  res.status(200).send('OK');
});</code></pre>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold mb-4">3. Verify Signatures</h3>
                        <p class="text-gray-600 mb-4">Always verify webhook signatures for security:</p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-6">
                            <pre class="text-green-400 text-sm"><code>const crypto = require('crypto');

function verifySignature(req) {
  const signature = req.headers['x-finaegis-signature'];
  const payload = JSON.stringify(req.body);
  const secret = process.env.WEBHOOK_SECRET;
  
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  
  return signature === expectedSignature;
}</code></pre>
                        </div>

                        <h3 class="text-xl font-semibold mb-4">4. Handle Retries</h3>
                        <p class="text-gray-600 mb-4">Implement idempotency for webhook retries:</p>
                        
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-green-400 text-sm"><code>const processedEvents = new Set();

app.post('/webhooks/finaegis', (req, res) => {
  const eventId = req.headers['x-finaegis-event-id'];
  
  // Check if already processed
  if (processedEvents.has(eventId)) {
    return res.status(200).send('Already processed');
  }
  
  // Process event
  processWebhookEvent(req.body);
  processedEvents.add(eventId);
  
  res.status(200).send('OK');
});</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Payload Examples -->
            <section class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Payload Examples</h2>
                
                <div class="space-y-8">
                    <div class="border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b">
                            <h3 class="text-xl font-semibold">Transfer Completed</h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>{
  "event_id": "evt_abc123def456",
  "event_type": "transfer.completed",
  "created_at": "2025-01-01T12:00:00Z",
  "data": {
    "uuid": "txfr_xyz789abc123",
    "from_account": "acct_sender123",
    "to_account": "acct_receiver456", 
    "amount": "100.00",
    "asset_code": "USD",
    "status": "completed",
    "reference": "Payment for services",
    "workflow_id": "wf_workflow123",
    "fees": {
      "transfer_fee": "0.50",
      "currency": "USD"
    },
    "completed_at": "2025-01-01T12:05:00Z"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b">
                            <h3 class="text-xl font-semibold">Balance Updated</h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>{
  "event_id": "evt_def456ghi789",
  "event_type": "account.balance_updated",
  "created_at": "2025-01-01T12:05:30Z",
  "data": {
    "account_uuid": "acct_receiver456",
    "asset_code": "USD",
    "previous_balance": "500.00",
    "new_balance": "600.00",
    "change_amount": "100.00",
    "change_reason": "incoming_transfer",
    "related_transaction": "txfr_xyz789abc123"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b">
                            <h3 class="text-xl font-semibold">Workflow Compensation</h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-green-400 text-sm"><code>{
  "event_id": "evt_ghi789jkl012",
  "event_type": "workflow.compensation_executed",
  "created_at": "2025-01-01T12:10:00Z",
  "data": {
    "workflow_id": "wf_failed123",
    "workflow_type": "currency_conversion_transfer",
    "failed_step": "transfer",
    "failure_reason": "insufficient_funds",
    "compensation_actions": [
      {
        "action": "reverse_currency_conversion",
        "status": "completed",
        "amount_reversed": "95.50",
        "asset_code": "EUR"
      }
    ],
    "compensation_completed_at": "2025-01-01T12:09:45Z"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Best Practices -->
            <section class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Best Practices</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="border-l-4 border-green-500 pl-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Security</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li>• Always verify webhook signatures</li>
                                <li>• Use HTTPS endpoints only</li>
                                <li>• Implement IP whitelisting if needed</li>
                                <li>• Store webhook secrets securely</li>
                            </ul>
                        </div>

                        <div class="border-l-4 border-blue-500 pl-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Performance</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li>• Respond with HTTP 200 quickly</li>
                                <li>• Process events asynchronously</li>
                                <li>• Implement proper timeout handling</li>
                                <li>• Use queues for heavy processing</li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="border-l-4 border-yellow-500 pl-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Reliability</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li>• Implement idempotency checks</li>
                                <li>• Handle duplicate events gracefully</li>
                                <li>• Log all webhook events</li>
                                <li>• Monitor webhook endpoint health</li>
                            </ul>
                        </div>

                        <div class="border-l-4 border-purple-500 pl-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Error Handling</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li>• Return proper HTTP status codes</li>
                                <li>• Implement exponential backoff</li>
                                <li>• Handle partial failures gracefully</li>
                                <li>• Set up alerting for failures</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testing -->
            <section class="bg-indigo-50 rounded-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Testing Webhooks</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Development Testing</h3>
                        <p class="text-gray-600 mb-4">Use ngrok or similar tools to expose your local development server:</p>
                        
                        <div class="bg-gray-900 rounded p-3 mb-4">
                            <code class="text-green-400 text-sm">ngrok http 3000</code>
                        </div>
                        
                        <p class="text-gray-600">Then configure your webhook URL to the ngrok tunnel.</p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Manual Testing</h3>
                        <p class="text-gray-600 mb-4">Test webhook delivery manually via API:</p>
                        
                        <div class="bg-gray-900 rounded p-3">
                            <pre class="text-green-400 text-sm"><code>curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -d '{"event_type": "test.webhook"}' \
  https://api.finaegis.com/v1/webhooks/{id}/test</code></pre>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-guest-layout>
@extends('layouts.public')

@section('title', 'API Documentation - FinAegis')

@section('seo')
    @include('partials.seo', [
        'title' => 'API Documentation - FinAegis',
        'description' => 'Complete reference documentation for the FinAegis REST API with interactive examples and code samples.',
        'keywords' => 'FinAegis API, REST API, API documentation, developer reference',
    ])
@endsection

@section('content')
    <div class="bg-white">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                        API Documentation
                    </h1>
                    <p class="mt-6 text-xl text-gray-300 max-w-3xl mx-auto">
                        Complete reference documentation for the FinAegis REST API with interactive examples and code samples.
                    </p>
                </div>
            </div>
        </div>

        <!-- API Navigation -->
        <div class="bg-gray-50 border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-4">
                    <a href="#getting-started" class="text-blue-600 font-medium">Getting Started</a>
                    <a href="#authentication" class="text-gray-600 hover:text-gray-900">Authentication</a>
                    <a href="#accounts" class="text-gray-600 hover:text-gray-900">Accounts</a>
                    <a href="#transactions" class="text-gray-600 hover:text-gray-900">Transactions</a>
                    <a href="#transfers" class="text-gray-600 hover:text-gray-900">Transfers</a>
                    <a href="#gcu" class="text-gray-600 hover:text-gray-900">GCU</a>
                    <a href="#assets" class="text-gray-600 hover:text-gray-900">Assets</a>
                    <a href="#baskets" class="text-gray-600 hover:text-gray-900">Baskets</a>
                    <a href="#webhooks" class="text-gray-600 hover:text-gray-900">Webhooks</a>
                    <a href="#errors" class="text-gray-600 hover:text-gray-900">Errors</a>
                </nav>
            </div>
        </div>

        <!-- API Documentation Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <section id="getting-started" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Getting Started</h2>
                        
                        <div class="prose prose-lg max-w-none">
                            <p>The FinAegis API provides programmatic access to our multi-asset banking platform. Our API is organized around REST principles with predictable, resource-oriented URLs.</p>
                            
                            <h3>Base URL</h3>
                            <x-code-block language="plaintext">
https://api.finaegis.org/v2
                            </x-code-block>
                            
                            <h3>Response Format</h3>
                            <p>All API responses are returned in JSON format with a consistent structure:</p>
                            
                            <x-code-block language="json">
{
  "data": { ... },          // Main response data
  "meta": { ... },          // Metadata (pagination, etc.)
  "links": { ... },         // Related links
  "errors": [ ... ]         // Error details (if any)
}
                            </x-code-block>
                        </div>
                    </section>

                    <section id="authentication" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Authentication</h2>
                        
                        <div class="prose prose-lg max-w-none">
                            <p>The FinAegis API uses API keys to authenticate requests. You can generate and manage your API keys in your dashboard.</p>
                            
                            <h3>Creating API Keys</h3>
                            <ol>
                                <li>Log in to your FinAegis account</li>
                                <li>Navigate to <a href="{{ route('api-keys.index') }}" class="text-blue-600 hover:text-blue-800">API Keys</a> in your dashboard</li>
                                <li>Click "Create New Key" and configure permissions</li>
                                <li>Copy the generated key immediately (it won't be shown again)</li>
                            </ol>
                            
                            <h3>API Key Authentication</h3>
                            <p>Include your API key in the Authorization header:</p>
                            
                            <x-code-block language="bash">
curl -H "Authorization: Bearer fak_your_api_key_here" \
     -H "Content-Type: application/json" \
     https://api.finaegis.org/v2/accounts
                            </x-code-block>
                            
                            <h3>API Key Security</h3>
                            <ul>
                                <li><strong>Permissions:</strong> Grant only the minimum required permissions (read, write, delete)</li>
                                <li><strong>IP Whitelist:</strong> Restrict API key usage to specific IP addresses</li>
                                <li><strong>Expiration:</strong> Set expiration dates for temporary keys</li>
                                <li><strong>Rotation:</strong> Regularly rotate your API keys</li>
                                <li><strong>Storage:</strong> Never commit API keys to version control</li>
                            </ul>
                            
                            <h3>Sandbox vs Production</h3>
                            <p>Use these base URLs for testing and production:</p>
                            <ul>
                                <li><strong>Sandbox:</strong> https://api-sandbox.finaegis.org/v2</li>
                                <li><strong>Production:</strong> https://api.finaegis.org/v2</li>
                            </ul>
                        </div>
                    </section>

                    <section id="accounts" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Accounts</h2>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">List Accounts</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/accounts</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Retrieve a list of all accounts for the authenticated user.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/accounts
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Account Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Retrieve detailed information about a specific account.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/accounts/acct_1234567890
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Account Balances</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/balances</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get current balances for all assets in an account.</p>
                                
                                <x-code-block language="json">
{
  "data": {
    "account_uuid": "acct_1234567890",
    "balances": [
      {
        "asset_code": "USD",
        "available_balance": "1500.00",
        "reserved_balance": "0.00",
        "total_balance": "1500.00"
      },
      {
        "asset_code": "EUR", 
        "available_balance": "1200.50",
        "reserved_balance": "50.00",
        "total_balance": "1250.50"
      }
    ],
    "summary": {
      "total_assets": 2,
      "total_usd_equivalent": "2,850.75"
    }
  }
}
                                </x-code-block>
                            </div>
                        </div>
                    </section>

                    <section id="transactions" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Transactions</h2>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">List Transactions</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/transactions</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get a paginated list of all transactions.</p>
                                
                                <h4 class="font-semibold mb-2">Query Parameters:</h4>
                                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                                    <li><code class="bg-gray-100 px-1">page</code> - Page number (default: 1)</li>
                                    <li><code class="bg-gray-100 px-1">per_page</code> - Items per page (default: 20, max: 100)</li>
                                    <li><code class="bg-gray-100 px-1">asset_code</code> - Filter by asset code</li>
                                    <li><code class="bg-gray-100 px-1">status</code> - Filter by status</li>
                                </ul>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     "https://api.finaegis.org/v2/transactions?page=1&per_page=20"
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Deposit Funds</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/deposit</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Deposit funds into an account.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "500.00",
    "asset_code": "USD",
    "reference": "Initial deposit"
  }' \
  https://api.finaegis.org/v2/accounts/acct_1234567890/deposit
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Withdraw Funds</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/withdraw</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Withdraw funds from an account.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "100.00",
    "asset_code": "USD",
    "reference": "ATM withdrawal"
  }' \
  https://api.finaegis.org/v2/accounts/acct_1234567890/withdraw
                                </x-code-block>
                            </div>
                        </div>
                    </section>

                    <section id="transfers" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Transfers</h2>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Create Transfer</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/transfers</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Create a new transfer between accounts.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "from_account": "acct_1234567890",
    "to_account": "acct_0987654321", 
    "amount": "100.00",
    "asset_code": "USD",
    "reference": "Payment for services",
    "workflow_enabled": true
  }' \
  https://api.finaegis.org/v2/transfers
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Transfer History</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/transfers</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get transfer history for a specific account.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/accounts/acct_1234567890/transfers
                                </x-code-block>
                            </div>
                        </div>
                    </section>

                    <section id="gcu" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Global Currency Unit (GCU)</h2>
                        
                        <div class="prose prose-lg max-w-none mb-8">
                            <p>The GCU endpoints provide access to real-time data about the Global Currency Unit, including its composition, value history, and governance information.</p>
                        </div>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get GCU Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get current information about the Global Currency Unit including composition and value.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/gcu
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Real-time GCU Composition</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/composition</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get detailed real-time composition data including current weights, values, and recent changes for each component asset.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/gcu/composition
                                </x-code-block>
                                
                                <h4 class="font-semibold mb-2">Response Example:</h4>
                                <x-code-block language="json">
{
  "data": {
    "basket_code": "GCU",
    "last_updated": "2024-01-15T10:30:00Z",
    "total_value_usd": 1.0975,
    "composition": [
      {
        "asset_code": "USD",
        "asset_name": "US Dollar",
        "asset_type": "fiat",
        "weight": 0.35,
        "current_price_usd": 1.0000,
        "value_contribution_usd": 0.3500,
        "percentage_of_basket": 31.89,
        "24h_change": 0.00,
        "7d_change": 0.00
      },
      {
        "asset_code": "EUR",
        "asset_name": "Euro", 
        "asset_type": "fiat",
        "weight": 0.30,
        "current_price_usd": 1.0850,
        "value_contribution_usd": 0.3255,
        "percentage_of_basket": 29.68,
        "24h_change": 0.15,
        "7d_change": -0.23
      }
    ],
    "rebalancing": {
      "frequency": "quarterly",
      "last_rebalanced": "2024-01-01T00:00:00Z",
      "next_rebalance": "2024-04-01T00:00:00Z",
      "automatic": true
    },
    "performance": {
      "24h_change_usd": 0.0025,
      "24h_change_percent": 0.23,
      "7d_change_usd": -0.0050,
      "7d_change_percent": -0.45,
      "30d_change_usd": 0.0175,
      "30d_change_percent": 1.62
    }
  }
}
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get GCU Value History</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/value-history</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get historical value data for the Global Currency Unit.</p>
                                
                                <h4 class="font-semibold mb-2">Query Parameters:</h4>
                                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                                    <li><code class="bg-gray-100 px-1">period</code> - Time period: 24h, 7d, 30d, 90d, 1y, all (default: 30d)</li>
                                    <li><code class="bg-gray-100 px-1">interval</code> - Data interval: hourly, daily, weekly, monthly (default: daily)</li>
                                </ul>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     "https://api.finaegis.org/v2/gcu/value-history?period=7d&interval=hourly"
                                </x-code-block>
                            </div>

                            <!-- Voting Endpoints -->
                            <div class="mt-12 mb-6">
                                <h3 class="text-2xl font-semibold text-gray-900">Democratic Voting System</h3>
                                <p class="text-gray-600 mt-2">The GCU voting system allows token holders to participate in monthly governance votes to optimize the currency basket composition.</p>
                            </div>

                            <div class="border rounded-lg p-6 mb-6">
                                <h3 class="text-xl font-semibold mb-4">List Voting Proposals</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/voting/proposals</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get all voting proposals with optional status filtering.</p>
                                
                                <h4 class="font-semibold mb-2">Query Parameters:</h4>
                                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                                    <li><code class="bg-gray-100 px-1">status</code> - Filter by status: active, upcoming, past (optional)</li>
                                </ul>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     "https://api.finaegis.org/v2/gcu/voting/proposals?status=active"
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6 mb-6">
                                <h3 class="text-xl font-semibold mb-4">Get Proposal Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/voting/proposals/{id}</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get detailed information about a specific voting proposal.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/gcu/voting/proposals/123
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6 mb-6">
                                <h3 class="text-xl font-semibold mb-4">Cast Vote</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/voting/proposals/{id}/vote</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">Requires Authentication</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Cast your vote on a proposal. Voting power is determined by your GCU balance.</p>
                                
                                <h4 class="font-semibold mb-2">Request Body:</h4>
                                <x-code-block language="json">
{
  "vote": "for"  // Options: "for", "against", "abstain"
}
                                </x-code-block>
                                
                                <x-code-block language="bash">
curl -X POST \
     -H "Authorization: Bearer your_api_key" \
     -H "Content-Type: application/json" \
     -d '{"vote": "for"}' \
     https://api.finaegis.org/v2/gcu/voting/proposals/123/vote
                                </x-code-block>
                            </div>

                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get My Voting History</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/v2/gcu/voting/my-votes</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">Requires Authentication</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get your voting history across all proposals.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/gcu/voting/my-votes
                                </x-code-block>
                            </div>
                        </div>
                    </section>

                    <section id="baskets" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Baskets</h2>
                        
                        <div class="prose prose-lg max-w-none mb-8">
                            <p>Baskets are multi-asset currency units that can be composed and decomposed. The GCU is our primary basket.</p>
                        </div>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">List Baskets</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/baskets</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get a list of all available baskets.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/baskets
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Basket Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/baskets/{code}</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get detailed information about a specific basket.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/baskets/GCU
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Compose Basket</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/baskets/compose</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Convert individual assets into basket units.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "basket_code": "GCU",
    "amount": "100.00"
  }' \
  https://api.finaegis.org/v2/accounts/acct_123/baskets/compose
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Decompose Basket</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/accounts/{uuid}/baskets/decompose</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Convert basket units back to individual assets.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "basket_code": "GCU",
    "amount": "50.00"
  }' \
  https://api.finaegis.org/v2/accounts/acct_123/baskets/decompose
                                </x-code-block>
                            </div>
                        </div>
                    </section>

                    <section id="webhooks" class="mb-16">
                        <h2 class="text-3xl font-bold text-gray-900 mb-8">Webhooks</h2>
                        
                        <div class="prose prose-lg max-w-none mb-8">
                            <p>Webhooks allow you to receive real-time notifications when events occur in your FinAegis account.</p>
                        </div>
                        
                        <div class="space-y-8">
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">List Webhook Events</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/webhooks/events</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get a list of all available webhook event types.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/webhooks/events
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Create Webhook</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">POST</span>
                                        <span class="ml-2 font-mono text-sm">/webhooks</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Create a new webhook endpoint.</p>
                                
                                <x-code-block language="bash">
curl -X POST \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com/webhook",
    "events": ["transaction.created", "transfer.completed"],
    "description": "Main webhook endpoint"
  }' \
  https://api.finaegis.org/v2/webhooks
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">List Webhooks</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/webhooks</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get all webhook endpoints for your account.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/webhooks
                                </x-code-block>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h3 class="text-xl font-semibold mb-4">Get Webhook Deliveries</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">GET</span>
                                        <span class="ml-2 font-mono text-sm">/webhooks/{id}/deliveries</span>
                                    </div>
                                </div>
                                
                                <p class="text-gray-600 mb-4">Get delivery history for a specific webhook.</p>
                                
                                <x-code-block language="bash">
curl -H "Authorization: Bearer your_api_key" \
     https://api.finaegis.org/v2/webhooks/webhook_123/deliveries
                                </x-code-block>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-8">
                        <div class="bg-white border rounded-lg p-6 mb-8">
                            <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                            <ul class="space-y-2">
                                <li><a href="{{ route('developers.show', 'sdks') }}" class="text-blue-600 hover:text-blue-800">Official SDKs</a></li>
                                <li><a href="{{ route('developers.show', 'postman') }}" class="text-blue-600 hover:text-blue-800">Postman Collection</a></li>
                                <li><a href="{{ route('developers.show', 'examples') }}" class="text-blue-600 hover:text-blue-800">Code Examples</a></li>
                                <li><a href="{{ route('developers.show', 'webhooks') }}" class="text-blue-600 hover:text-blue-800">Webhooks Guide</a></li>
                            </ul>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-blue-900 mb-4">OpenAPI Specification</h3>
                            <p class="text-blue-800 mb-4">Download the OpenAPI specification file or view it in your preferred API client.</p>
                            <div class="flex gap-3">
                                <a href="/docs/api-docs.json" download="finaegis-api-v2.json" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-200">
                                    Download OpenAPI JSON
                                </a>
                                <a href="/api/documentation" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-200">
                                    Interactive API Explorer
                                </a>
                            </div>
                            <p class="text-xs text-gray-600 mt-3">Import the JSON file into Postman, Insomnia, or any OpenAPI-compatible tool</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function copyCode(button) {
    const codeBlock = button.parentElement.querySelector('code');
    const text = codeBlock.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        button.classList.add('text-green-400');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('text-green-400');
        }, 2000);
    });
}
</script>
@endpush
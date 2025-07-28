@extends('layouts.public')

@section('title', 'Code Examples - FinAegis')

@section('seo')
    @include('partials.seo', [
        'title' => 'Code Examples - FinAegis',
        'description' => 'Working examples and integration patterns for common use cases with the FinAegis API.',
        'keywords' => 'FinAegis API examples, code samples, integration patterns, developer examples',
    ])
@endsection

@push('styles')
<style>
    /* Tab styling */
    .code-tabs {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 1.5rem;
        background: #f3f4f6;
        padding: 0.25rem;
        border-radius: 0.5rem;
    }
    .code-tab {
        padding: 0.5rem 1rem;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        border-radius: 0.375rem;
        transition: all 0.2s;
        font-size: 0.875rem;
    }
    .code-tab:hover {
        color: #374151;
        background: #e5e7eb;
    }
    .code-tab.active {
        color: white;
        background: #4f46e5;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    /* Code example wrapper */
    .code-example-wrapper {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .code-example-header {
        background: #f9fafb;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
</style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-green-600 to-blue-600 text-white">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Code Examples
                </h1>
                <p class="text-xl md:text-2xl text-green-100 max-w-3xl mx-auto">
                    Working examples and integration patterns for common use cases with the FinAegis API.
                </p>
            </div>
        </div>
    </section>

    <!-- Example Categories -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Browse by Category</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="#basic-operations" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 hover:transform hover:-translate-y-1">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Basic Operations</h3>
                    <p class="text-gray-600 text-sm">Account creation, balance queries, and simple transfers</p>
                </a>

                <a href="#advanced-workflows" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 hover:transform hover:-translate-y-1">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-green-200 transition-colors">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Advanced Workflows</h3>
                    <p class="text-gray-600 text-sm">Multi-step transactions, batch operations, and saga patterns</p>
                </a>

                <a href="#webhooks" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 hover:transform hover:-translate-y-1">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v2a2 2 0 002 2h4a2 2 0 002-2v-2h2a2 2 0 002-2V9a2 2 0 00-2-2h-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v2H4a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Webhooks</h3>
                    <p class="text-gray-600 text-sm">Real-time notifications and event handling</p>
                </a>

                <a href="#error-handling" class="group bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6 hover:transform hover:-translate-y-1">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-red-200 transition-colors">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Error Handling</h3>
                    <p class="text-gray-600 text-sm">Robust error handling and retry patterns</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Examples Content -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Basic Operations -->
            <div id="basic-operations" class="mb-20">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Basic Operations</h2>
                
                <div class="space-y-12">
                    <!-- Create Account Example -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-5 border-b">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Create Account and Get Balance</h3>
                                    <p class="text-gray-600 mt-1">Initialize a new account and retrieve balance information</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">POST</span>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">GET</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Tabs -->
                            <div class="code-tabs">
                                <button onclick="switchTab(event, 'create-js')" class="code-tab active">JavaScript</button>
                                <button onclick="switchTab(event, 'create-py')" class="code-tab">Python</button>
                                <button onclick="switchTab(event, 'create-php')" class="code-tab">PHP</button>
                                <button onclick="switchTab(event, 'create-curl')" class="code-tab">cURL</button>
                            </div>
                            
                            <!-- JavaScript -->
                            <div id="create-js" class="tab-content active animate-fade-in">
                                <x-code-block language="javascript">
import { FinAegis } from '@finaegis/sdk';

const client = new FinAegis({
  apiKey: process.env.FINAEGIS_API_KEY,
  baseURL: 'https://api.finaegis.org/v2'
});

async function createAccountAndCheckBalance() {
  try {
    // Create a new account
    const account = await client.accounts.create({
      name: 'My Main Account',
      type: 'personal',
      metadata: {
        customer_id: 'cust_123',
        purpose: 'savings'
      }
    });
    
    console.log('Account created:', account.uuid);
    
    // Get account balances
    const balances = await client.accounts.getBalances(account.uuid);
    
    console.log('Current balances:');
    balances.data.balances.forEach(balance => {
      console.log(`${balance.asset_code}: ${balance.available_balance}`);
    });
    
    return account;
  } catch (error) {
    console.error('Error:', error.message);
    throw error;
  }
}

createAccountAndCheckBalance();
                                </x-code-block>
                            </div>
                            
                            <!-- Python -->
                            <div id="create-py" class="tab-content animate-fade-in">
                                <x-code-block language="python">
from finaegis import FinAegis
import os

client = FinAegis(
    api_key=os.environ['FINAEGIS_API_KEY'],
    base_url='https://api.finaegis.org/v2'
)

def create_account_and_check_balance():
    try:
        # Create a new account
        account = client.accounts.create(
            name='My Main Account',
            type='personal',
            metadata={
                'customer_id': 'cust_123',
                'purpose': 'savings'
            }
        )
        
        print(f'Account created: {account.uuid}')
        
        # Get account balances
        balances = client.accounts.get_balances(account.uuid)
        
        print('Current balances:')
        for balance in balances.data.balances:
            print(f'{balance.asset_code}: {balance.available_balance}')
        
        return account
    except Exception as error:
        print(f'Error: {error}')
        raise

create_account_and_check_balance()
                                </x-code-block>
                            </div>
                            
                            <!-- PHP -->
                            <div id="create-php" class="tab-content animate-fade-in">
                                <x-code-block language="php">
require_once 'vendor/autoload.php';

use FinAegis\Client;

$client = new Client([
    'apiKey' => $_ENV['FINAEGIS_API_KEY'],
    'baseURL' => 'https://api.finaegis.org/v2'
]);

function createAccountAndCheckBalance($client) {
    try {
        // Create a new account
        $account = $client->accounts->create([
            'name' => 'My Main Account',
            'type' => 'personal',
            'metadata' => [
                'customer_id' => 'cust_123',
                'purpose' => 'savings'
            ]
        ]);
        
        echo "Account created: {$account->uuid}\n";
        
        // Get account balances
        $balances = $client->accounts->getBalances($account->uuid);
        
        echo "Current balances:\n";
        foreach ($balances->data->balances as $balance) {
            echo "{$balance->asset_code}: {$balance->available_balance}\n";
        }
        
        return $account;
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        throw $e;
    }
}

createAccountAndCheckBalance($client);
                                </x-code-block>
                            </div>
                            
                            <!-- cURL -->
                            <div id="create-curl" class="tab-content animate-fade-in">
                                <x-code-block language="bash">
# Create a new account
curl -X POST https://api.finaegis.org/v2/accounts \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Main Account",
    "type": "personal",
    "metadata": {
      "customer_id": "cust_123",
      "purpose": "savings"
    }
  }'

# Response
{
  "data": {
    "uuid": "acct_1234567890abcdef",
    "name": "My Main Account",
    "type": "personal",
    "status": "active",
    "created_at": "2025-01-01T12:00:00Z"
  }
}

# Get account balances
curl -X GET https://api.finaegis.org/v2/accounts/acct_1234567890abcdef/balances \
  -H "Authorization: Bearer YOUR_API_KEY"
                                </x-code-block>
                            </div>
                        </div>
                    </div>

                    <!-- Simple Transfer Example -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-5 border-b">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Simple Money Transfer</h3>
                                    <p class="text-gray-600 mt-1">Transfer funds between accounts with automatic workflow processing</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">POST</span>
                                    <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">Workflow</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Tabs -->
                            <div class="code-tabs">
                                <button onclick="switchTab(event, 'transfer-js')" class="code-tab active">JavaScript</button>
                                <button onclick="switchTab(event, 'transfer-py')" class="code-tab">Python</button>
                                <button onclick="switchTab(event, 'transfer-response')" class="code-tab">Response</button>
                            </div>
                            
                            <!-- JavaScript -->
                            <div id="transfer-js" class="tab-content active animate-fade-in">
                                <x-code-block language="javascript">
async function transferFunds(fromAccount, toAccount, amount) {
  try {
    const transfer = await client.transfers.create({
      from_account: fromAccount,
      to_account: toAccount,
      amount: amount,
      asset_code: 'USD',
      reference: 'Payment for services',
      workflow_enabled: true,
      metadata: {
        invoice_id: 'INV-2025-001',
        payment_type: 'service'
      }
    });
    
    console.log('Transfer initiated:', transfer.uuid);
    console.log('Status:', transfer.status);
    console.log('Workflow ID:', transfer.workflow_id);
    
    // Poll for completion
    const completed = await client.transfers.waitForCompletion(
      transfer.uuid, 
      { timeout: 30000 }
    );
    
    console.log('Transfer completed:', completed.status);
    return completed;
  } catch (error) {
    console.error('Transfer failed:', error.message);
    throw error;
  }
}

// Usage
transferFunds(
  'acct_1234567890',
  'acct_0987654321', 
  '100.00'
);
                                </x-code-block>
                            </div>
                            
                            <!-- Python -->
                            <div id="transfer-py" class="tab-content animate-fade-in">
                                <x-code-block language="python">
def transfer_funds(from_account, to_account, amount):
    try:
        transfer = client.transfers.create(
            from_account=from_account,
            to_account=to_account,
            amount=amount,
            asset_code='USD',
            reference='Payment for services',
            workflow_enabled=True,
            metadata={
                'invoice_id': 'INV-2025-001',
                'payment_type': 'service'
            }
        )
        
        print(f'Transfer initiated: {transfer.uuid}')
        print(f'Status: {transfer.status}')
        print(f'Workflow ID: {transfer.workflow_id}')
        
        # Poll for completion
        completed = client.transfers.wait_for_completion(
            transfer.uuid,
            timeout=30000
        )
        
        print(f'Transfer completed: {completed.status}')
        return completed
    except Exception as error:
        print(f'Transfer failed: {error}')
        raise

# Usage
transfer_funds(
    'acct_1234567890',
    'acct_0987654321',
    '100.00'
)
                                </x-code-block>
                            </div>
                            
                            <!-- Response -->
                            <div id="transfer-response" class="tab-content animate-fade-in">
                                <x-code-block language="json">
{
  "data": {
    "uuid": "txfr_abc123def456",
    "from_account": "acct_1234567890",
    "to_account": "acct_0987654321",
    "amount": "100.00",
    "asset_code": "USD",
    "status": "pending",
    "reference": "Payment for services",
    "workflow_id": "wf_789xyz012",
    "created_at": "2025-01-01T12:00:00Z",
    "estimated_completion": "2025-01-01T12:05:00Z",
    "metadata": {
      "invoice_id": "INV-2025-001",
      "payment_type": "service"
    },
    "fees": {
      "transfer_fee": "0.50",
      "currency": "USD"
    }
  }
}
                                </x-code-block>
                            </div>
                        </div>
                    </div>
                    <!-- List Transactions Example -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-5 border-b">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">List Account Transactions</h3>
                                    <p class="text-gray-600 mt-1">Retrieve transaction history with pagination and filtering</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">GET</span>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Paginated</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Tabs -->
                            <div class="code-tabs">
                                <button onclick="switchTab(event, 'list-js')" class="code-tab active">JavaScript</button>
                                <button onclick="switchTab(event, 'list-curl')" class="code-tab">cURL</button>
                                <button onclick="switchTab(event, 'list-response')" class="code-tab">Response</button>
                            </div>
                            
                            <!-- JavaScript -->
                            <div id="list-js" class="tab-content active animate-fade-in">
                                <x-code-block language="javascript">
async function getAccountTransactions(accountId, options = {}) {
  try {
    const transactions = await client.accounts.getTransactions(accountId, {
      limit: options.limit || 20,
      page: options.page || 1,
      type: options.type, // 'deposit', 'withdrawal', 'transfer'
      status: options.status, // 'pending', 'completed', 'failed'
      start_date: options.startDate,
      end_date: options.endDate,
      sort: options.sort || '-created_at' // - for descending
    });
    
    console.log(`Found ${transactions.meta.total} transactions`);
    console.log(`Showing page ${transactions.meta.current_page} of ${transactions.meta.last_page}`);
    
    transactions.data.forEach(tx => {
      console.log(`${tx.created_at}: ${tx.type} ${tx.amount} ${tx.asset_code} - ${tx.status}`);
    });
    
    return transactions;
  } catch (error) {
    console.error('Failed to fetch transactions:', error.message);
    throw error;
  }
}

// Usage examples
// Get all transactions
getAccountTransactions('acct_1234567890');

// Get withdrawals from last 30 days
getAccountTransactions('acct_1234567890', {
  type: 'withdrawal',
  startDate: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString()
});
                                </x-code-block>
                            </div>
                            
                            <!-- cURL -->
                            <div id="list-curl" class="tab-content animate-fade-in">
                                <x-code-block language="bash">
# Get all transactions for an account
curl -X GET "https://api.finaegis.org/v2/accounts/acct_1234567890/transactions" \
  -H "Authorization: Bearer YOUR_API_KEY"

# Get transactions with filters
curl -X GET "https://api.finaegis.org/v2/accounts/acct_1234567890/transactions?\
limit=20&\
page=1&\
type=withdrawal&\
status=completed&\
start_date=2025-01-01T00:00:00Z&\
end_date=2025-01-31T23:59:59Z&\
sort=-created_at" \
  -H "Authorization: Bearer YOUR_API_KEY"
                                </x-code-block>
                            </div>
                            
                            <!-- Response -->
                            <div id="list-response" class="tab-content animate-fade-in">
                                <x-code-block language="json">
{
  "data": [
    {
      "uuid": "tx_789xyz123abc",
      "account_uuid": "acct_1234567890",
      "type": "deposit",
      "amount": "500.00",
      "asset_code": "USD",
      "status": "completed",
      "reference": "Salary deposit",
      "created_at": "2025-01-15T10:30:00Z",
      "completed_at": "2025-01-15T10:30:05Z",
      "balance_after": "1500.00"
    },
    {
      "uuid": "tx_456def789ghi",
      "account_uuid": "acct_1234567890",
      "type": "withdrawal",
      "amount": "100.00",
      "asset_code": "USD",
      "status": "completed",
      "reference": "ATM withdrawal",
      "created_at": "2025-01-14T15:45:00Z",
      "completed_at": "2025-01-14T15:45:10Z",
      "balance_after": "1000.00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "https://api.finaegis.org/v2/accounts/acct_1234567890/transactions?page=1",
    "last": "https://api.finaegis.org/v2/accounts/acct_1234567890/transactions?page=5",
    "prev": null,
    "next": "https://api.finaegis.org/v2/accounts/acct_1234567890/transactions?page=2"
  }
}
                                </x-code-block>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Workflows -->
            <div id="advanced-workflows" class="mb-20">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Advanced Workflows</h2>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h3 class="text-xl font-semibold text-gray-900">Multi-Currency Conversion with Transfer</h3>
                        <p class="text-gray-600 mt-1">Convert currency and transfer in one workflow</p>
                    </div>
                    
                    <div class="p-6">
                        <x-code-block language="javascript" title="JavaScript Implementation">
async function convertAndTransfer(fromAccount, toAccount, amount, fromCurrency, toCurrency) {
  try {
    // Get current exchange rate
    const rate = await client.exchangeRates.get(fromCurrency, toCurrency);
    const convertedAmount = (parseFloat(amount) * rate.rate).toFixed(2);
    
    console.log(`Exchange rate: 1 ${fromCurrency} = ${rate.rate} ${toCurrency}`);
    console.log(`Converting ${amount} ${fromCurrency} to ${convertedAmount} ${toCurrency}`);
    
    // Create workflow for conversion + transfer
    const workflow = await client.workflows.create({
      type: 'currency_conversion_transfer',
      steps: [
        {
          type: 'currency_conversion',
          from_account: fromAccount,
          amount: amount,
          from_currency: fromCurrency,
          to_currency: toCurrency,
          max_slippage: 0.01 // 1% max slippage
        },
        {
          type: 'transfer',
          from_account: fromAccount,
          to_account: toAccount,
          amount: convertedAmount,
          asset_code: toCurrency,
          reference: 'Converted payment'
        }
      ],
      compensation_enabled: true
    });
    
    console.log('Workflow started:', workflow.uuid);
    
    // Monitor workflow progress
    const result = await client.workflows.monitor(workflow.uuid, {
      onProgress: (step) => console.log(`Step ${step.name}: ${step.status}`),
      timeout: 60000
    });
    
    return result;
  } catch (error) {
    console.error('Workflow failed:', error.message);
    
    // Check if compensation was executed
    if (error.compensation_executed) {
      console.log('Automatic rollback completed');
    }
    
    throw error;
  }
}

convertAndTransfer(
  'acct_source123', 
  'acct_dest456', 
  '1000.00', 
  'USD', 
  'EUR'
);
                        </x-code-block>
                    </div>
                </div>
            </div>

            <!-- Webhooks -->
            <div id="webhooks" class="mb-20">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Webhooks</h2>
                
                <div class="space-y-8">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b">
                            <h3 class="text-xl font-semibold text-gray-900">Setting Up Webhook Endpoints</h3>
                            <p class="text-gray-600 mt-1">Handle real-time events from FinAegis</p>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Express.js Handler</h4>
                                    <x-code-block language="javascript">
const express = require('express');
const crypto = require('crypto');
const app = express();

// Middleware to verify webhook signatures
function verifyWebhookSignature(req, res, next) {
  const signature = req.headers['x-finaegis-signature'];
  const payload = JSON.stringify(req.body);
  const secret = process.env.WEBHOOK_SECRET;
  
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  
  if (signature !== expectedSignature) {
    return res.status(401).send('Invalid signature');
  }
  
  next();
}

// Webhook endpoint
app.post('/webhooks/finaegis', 
  express.json(),
  verifyWebhookSignature,
  (req, res) => {
    const { event_type, data } = req.body;
    
    console.log(`Received webhook: ${event_type}`);
    
    switch (event_type) {
      case 'transfer.completed':
        handleTransferCompleted(data);
        break;
      case 'transfer.failed':
        handleTransferFailed(data);
        break;
      case 'account.balance_updated':
        handleBalanceUpdated(data);
        break;
      case 'workflow.completed':
        handleWorkflowCompleted(data);
        break;
      case 'workflow.compensation_executed':
        handleCompensationExecuted(data);
        break;
      default:
        console.log(`Unhandled event type: ${event_type}`);
    }
    
    res.status(200).send('OK');
  }
);

async function handleTransferCompleted(transfer) {
  console.log(`Transfer ${transfer.uuid} completed`);
  
  // Update your database
  await updateTransferStatus(transfer.uuid, 'completed');
  
  // Send notification to user
  await notifyUser(transfer.from_account, {
    type: 'transfer_completed',
    amount: transfer.amount,
    currency: transfer.asset_code,
    reference: transfer.reference
  });
}

async function handleWorkflowCompleted(workflow) {
  console.log(`Workflow ${workflow.uuid} completed successfully`);
  
  // Process completion logic
  await processWorkflowCompletion(workflow);
}
                                    </x-code-block>
                                </div>
                                
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Webhook Configuration</h4>
                                    <x-code-block language="javascript">
// Configure webhook endpoints
async function setupWebhooks() {
  const webhook = await client.webhooks.create({
    url: 'https://yourapp.com/webhooks/finaegis',
    events: [
      'transfer.completed',
      'transfer.failed',
      'account.balance_updated',
      'workflow.completed',
      'workflow.failed',
      'workflow.compensation_executed'
    ],
    secret: process.env.WEBHOOK_SECRET,
    active: true
  });
  
  console.log('Webhook configured:', webhook.id);
  return webhook;
}

// Test webhook delivery
async function testWebhook(webhookId) {
  const result = await client.webhooks.test(webhookId, {
    event_type: 'test.webhook',
    data: { message: 'Test webhook delivery' }
  });
  
  console.log('Test result:', result.status);
}
                                    </x-code-block>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Handling -->
            <div id="error-handling" class="mb-20">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Error Handling</h2>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h3 class="text-xl font-semibold text-gray-900">Robust Error Handling with Retries</h3>
                        <p class="text-gray-600 mt-1">Handle failures gracefully with automatic retries</p>
                    </div>
                    
                    <div class="p-6">
                        <x-code-block language="javascript" title="Complete Error Handling Implementation">
class FinAegisWrapper {
  constructor(apiKey, options = {}) {
    this.client = new FinAegis({
      apiKey,
      baseURL: options.baseURL || 'https://api.finaegis.org/v2'
    });
    
    this.retryOptions = {
      maxRetries: options.maxRetries || 3,
      backoffMultiplier: options.backoffMultiplier || 2,
      initialDelay: options.initialDelay || 1000
    };
  }
  
  async withRetry(operation, context = '') {
    let lastError;
    let delay = this.retryOptions.initialDelay;
    
    for (let attempt = 1; attempt <= this.retryOptions.maxRetries + 1; attempt++) {
      try {
        return await operation();
      } catch (error) {
        lastError = error;
        
        console.log(`Attempt ${attempt} failed for ${context}:`, error.message);
        
        // Don't retry on client errors (4xx)
        if (error.status >= 400 && error.status < 500) {
          throw error;
        }
        
        // Don't retry on the last attempt
        if (attempt > this.retryOptions.maxRetries) {
          break;
        }
        
        // Wait before retrying
        console.log(`Retrying in ${delay}ms...`);
        await new Promise(resolve => setTimeout(resolve, delay));
        delay *= this.retryOptions.backoffMultiplier;
      }
    }
    
    throw lastError;
  }
  
  async createTransfer(transferData) {
    return this.withRetry(
      () => this.client.transfers.create(transferData),
      `transfer creation`
    );
  }
  
  async getAccountBalance(accountId) {
    return this.withRetry(
      () => this.client.accounts.getBalances(accountId),
      `balance query for ${accountId}`
    );
  }
  
  async executeWithFallback(primaryOperation, fallbackOperation, context) {
    try {
      return await this.withRetry(primaryOperation, context);
    } catch (primaryError) {
      console.log(`Primary operation failed, trying fallback:`, primaryError.message);
      
      try {
        return await this.withRetry(fallbackOperation, `${context} (fallback)`);
      } catch (fallbackError) {
        console.error('Both primary and fallback operations failed');
        throw new Error(`All operations failed. Primary: ${primaryError.message}, Fallback: ${fallbackError.message}`);
      }
    }
  }
}

// Usage example
const finAegis = new FinAegisWrapper(process.env.FINAEGIS_API_KEY, {
  environment: 'production',
  maxRetries: 3,
  backoffMultiplier: 2,
  initialDelay: 1000
});

async function robustTransfer(fromAccount, toAccount, amount) {
  try {
    // Primary: Direct transfer
    const primaryTransfer = () => finAegis.createTransfer({
      from_account: fromAccount,
      to_account: toAccount,
      amount: amount,
      asset_code: 'USD',
      workflow_enabled: true
    });
    
    // Fallback: Transfer via intermediate account
    const fallbackTransfer = () => finAegis.createTransfer({
      from_account: fromAccount,
      to_account: toAccount,
      amount: amount,
      asset_code: 'USD',
      workflow_enabled: true,
      routing: 'alternative'
    });
    
    const result = await finAegis.executeWithFallback(
      primaryTransfer,
      fallbackTransfer,
      'USD transfer'
    );
    
    console.log('Transfer successful:', result.uuid);
    return result;
    
  } catch (error) {
    console.error('Transfer completely failed:', error.message);
    
    // Log for monitoring
    await logTransferFailure({
      fromAccount,
      toAccount,
      amount,
      error: error.message,
      timestamp: new Date().toISOString()
    });
    
    throw error;
  }
}
                        </x-code-block>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-br from-green-600 to-blue-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Start Building Today</h2>
            <p class="text-xl text-green-100 mb-8">Use these examples as a starting point for your FinAegis integration.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 bg-white text-green-600 rounded-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                    Get API Keys
                </a>
                <a href="{{ route('developers.show', 'api-docs') }}" class="inline-flex items-center justify-center px-8 py-3 bg-green-700 text-white rounded-lg font-semibold hover:bg-green-800 transition-all border border-green-500">
                    API Documentation
                </a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    function copyCode(button) {
        const codeBlock = button.parentElement.querySelector('code');
        const text = codeBlock.textContent;
        
        navigator.clipboard.writeText(text).then(() => {
            const originalContent = button.innerHTML;
            button.innerHTML = '<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            
            setTimeout(() => {
                button.innerHTML = originalContent;
            }, 2000);
        });
    }
    
    function switchTab(event, tabId) {
        // Get the parent container
        const container = event.target.closest('.p-6') || event.target.closest('.p-8');
        
        // Hide all tab contents in this container
        const tabContents = container.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
            content.classList.add('animate-fade-in');
        });
        
        // Remove active class from all tabs in this container
        const tabs = container.querySelectorAll('.code-tab');
        tabs.forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show the selected tab content
        const selectedContent = container.querySelector('#' + tabId);
        if (selectedContent) {
            selectedContent.classList.add('active');
        }
        
        // Add active class to clicked tab
        event.target.classList.add('active');
    }
</script>
@endpush
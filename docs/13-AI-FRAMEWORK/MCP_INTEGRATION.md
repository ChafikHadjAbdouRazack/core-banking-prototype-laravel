# MCP (Model Context Protocol) Integration Guide

## Overview

The Model Context Protocol (MCP) enables standardized communication between AI systems and application backends. FinAegis implements a comprehensive MCP server that exposes banking operations as tools and resources that AI agents can interact with.

## Architecture

### MCP Server Implementation

```php
namespace App\AI\MCP;

class FinAegisMCPServer implements MCPServerInterface
{
    public function __construct(
        private ToolRegistry $tools,
        private ResourceManager $resources,
        private ContextManager $context
    ) {}
    
    public function getCapabilities(): array
    {
        return [
            'tools' => $this->tools->list(),
            'resources' => $this->resources->list(),
            'prompts' => $this->getPrompts(),
            'sampling' => true
        ];
    }
}
```

## Available Tools

### Account Management

#### account.create
Create a new bank account.

```json
{
  "name": "account.create",
  "description": "Create a new bank account",
  "inputSchema": {
    "type": "object",
    "properties": {
      "name": { "type": "string" },
      "currency": { "type": "string" },
      "initial_deposit": { "type": "number" }
    },
    "required": ["name", "currency"]
  }
}
```

#### account.balance
Get account balance.

```json
{
  "name": "account.balance",
  "description": "Get current account balance",
  "inputSchema": {
    "type": "object",
    "properties": {
      "account_uuid": { "type": "string" },
      "asset_code": { "type": "string" }
    },
    "required": ["account_uuid"]
  }
}
```

### Payment Operations

#### payment.transfer
Execute a payment transfer.

```json
{
  "name": "payment.transfer",
  "description": "Transfer funds between accounts",
  "inputSchema": {
    "type": "object",
    "properties": {
      "from_account": { "type": "string" },
      "to_account": { "type": "string" },
      "amount": { "type": "number" },
      "currency": { "type": "string" },
      "reference": { "type": "string" }
    },
    "required": ["from_account", "to_account", "amount", "currency"]
  }
}
```

### Trading Operations

#### exchange.quote
Get exchange rate quote.

```json
{
  "name": "exchange.quote",
  "description": "Get current exchange rate",
  "inputSchema": {
    "type": "object",
    "properties": {
      "from_currency": { "type": "string" },
      "to_currency": { "type": "string" },
      "amount": { "type": "number" }
    },
    "required": ["from_currency", "to_currency", "amount"]
  }
}
```

#### exchange.trade
Execute a trade.

```json
{
  "name": "exchange.trade",
  "description": "Execute currency exchange",
  "inputSchema": {
    "type": "object",
    "properties": {
      "account_uuid": { "type": "string" },
      "from_currency": { "type": "string" },
      "to_currency": { "type": "string" },
      "amount": { "type": "number" },
      "type": { "enum": ["market", "limit"] }
    },
    "required": ["account_uuid", "from_currency", "to_currency", "amount"]
  }
}
```

### Compliance Operations

#### compliance.kyc_check
Perform KYC verification.

```json
{
  "name": "compliance.kyc_check",
  "description": "Perform KYC verification",
  "inputSchema": {
    "type": "object",
    "properties": {
      "user_uuid": { "type": "string" },
      "documents": { "type": "array" }
    },
    "required": ["user_uuid"]
  }
}
```

#### compliance.transaction_check
Check transaction for compliance.

```json
{
  "name": "compliance.transaction_check",
  "description": "Check transaction compliance",
  "inputSchema": {
    "type": "object",
    "properties": {
      "transaction_id": { "type": "string" },
      "check_type": { "enum": ["aml", "sanctions", "all"] }
    },
    "required": ["transaction_id"]
  }
}
```

## Available Resources

### Ledger Resources

```typescript
// Access account information
"ledger://accounts/{uuid}"
"ledger://transactions/{uuid}"
"ledger://balances/{account_uuid}"
```

### Exchange Resources

```typescript
// Access market data
"exchange://orderbook/{pair}"
"exchange://rates/{from}/{to}"
"exchange://trades/{account_uuid}"
```

### Compliance Resources

```typescript
// Access compliance data
"compliance://kyc/{user_uuid}"
"compliance://reports/{report_id}"
"compliance://alerts/{account_uuid}"
```

## Implementation Examples

### PHP Implementation

```php
namespace App\AI\MCP\Tools;

class AccountBalanceTool implements MCPTool
{
    public function __construct(
        private AccountService $accountService
    ) {}
    
    public function getName(): string
    {
        return 'account.balance';
    }
    
    public function execute(array $params): array
    {
        $account = $this->accountService->find($params['account_uuid']);
        
        if (!$account) {
            throw new AccountNotFoundException();
        }
        
        $balance = $account->getBalance($params['asset_code'] ?? 'USD');
        
        return [
            'account_uuid' => $account->uuid,
            'balance' => $balance,
            'currency' => $params['asset_code'] ?? 'USD',
            'formatted' => money($balance, $params['asset_code'] ?? 'USD')
        ];
    }
    
    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'account_uuid' => ['type' => 'string'],
                'asset_code' => ['type' => 'string']
            ],
            'required' => ['account_uuid']
        ];
    }
}
```

### Tool Registration

```php
// In AppServiceProvider or AIServiceProvider

public function boot()
{
    $toolRegistry = app(ToolRegistry::class);
    
    // Register account tools
    $toolRegistry->register(new AccountCreateTool($this->app->make(AccountService::class)));
    $toolRegistry->register(new AccountBalanceTool($this->app->make(AccountService::class)));
    
    // Register payment tools
    $toolRegistry->register(new PaymentTransferTool($this->app->make(PaymentService::class)));
    
    // Register exchange tools
    $toolRegistry->register(new ExchangeQuoteTool($this->app->make(ExchangeService::class)));
    $toolRegistry->register(new ExchangeTradeTool($this->app->make(ExchangeService::class)));
    
    // Register compliance tools
    $toolRegistry->register(new ComplianceKYCTool($this->app->make(ComplianceService::class)));
}
```

## Security Considerations

### Authentication

All MCP requests must be authenticated:

```php
class MCPAuthMiddleware
{
    public function handle(MCPRequest $request, Closure $next)
    {
        $token = $request->getAuthToken();
        
        if (!$this->validateToken($token)) {
            throw new UnauthorizedException();
        }
        
        $request->setUser($this->getUserFromToken($token));
        
        return $next($request);
    }
}
```

### Authorization

Tools check permissions before execution:

```php
class AuthorizedTool implements MCPTool
{
    public function execute(array $params): array
    {
        $user = request()->user();
        
        if (!$user->can('execute', $this->getName())) {
            throw new ForbiddenException();
        }
        
        return $this->performAction($params);
    }
}
```

### Audit Logging

Every MCP operation is logged:

```php
class MCPAuditLogger
{
    public function logToolExecution(string $tool, array $params, array $result)
    {
        MCPAuditLog::create([
            'tool' => $tool,
            'user_id' => auth()->id(),
            'params' => $params,
            'result' => $result,
            'ip_address' => request()->ip(),
            'timestamp' => now()
        ]);
    }
}
```

## Testing MCP Integration

### Unit Tests

```php
class MCPToolTest extends TestCase
{
    public function test_account_balance_tool()
    {
        $account = Account::factory()->create(['balance' => 10000]);
        
        $tool = new AccountBalanceTool(app(AccountService::class));
        
        $result = $tool->execute([
            'account_uuid' => $account->uuid,
            'asset_code' => 'USD'
        ]);
        
        $this->assertEquals(10000, $result['balance']);
        $this->assertEquals('USD', $result['currency']);
    }
}
```

### Integration Tests

```php
class MCPServerTest extends TestCase
{
    public function test_mcp_server_lists_tools()
    {
        $response = $this->postJson('/mcp', [
            'method' => 'tools/list'
        ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'tools' => [
                    '*' => ['name', 'description', 'inputSchema']
                ]
            ]);
    }
    
    public function test_mcp_server_executes_tool()
    {
        $account = Account::factory()->create();
        
        $response = $this->postJson('/mcp', [
            'method' => 'tools/call',
            'params' => [
                'name' => 'account.balance',
                'arguments' => [
                    'account_uuid' => $account->uuid
                ]
            ]
        ]);
        
        $response->assertOk()
            ->assertJsonPath('result.account_uuid', $account->uuid);
    }
}
```

## Client Integration

### JavaScript/TypeScript Client

```typescript
import { MCPClient } from '@modelcontextprotocol/sdk';

const client = new MCPClient({
  url: 'https://api.finaegis.org/mcp',
  apiKey: process.env.MCP_API_KEY
});

// List available tools
const tools = await client.listTools();

// Execute a tool
const result = await client.executeTool('account.balance', {
  account_uuid: 'acc_123456',
  asset_code: 'USD'
});

console.log(`Balance: ${result.formatted}`);
```

### Python Client

```python
from mcp import MCPClient

client = MCPClient(
    url="https://api.finaegis.org/mcp",
    api_key=os.environ["MCP_API_KEY"]
)

# Get account balance
result = client.execute_tool(
    "account.balance",
    account_uuid="acc_123456",
    asset_code="USD"
)

print(f"Balance: {result['formatted']}")
```

## Best Practices

1. **Tool Granularity**: Keep tools focused on single operations
2. **Error Handling**: Return clear, actionable error messages
3. **Validation**: Validate all inputs before processing
4. **Idempotency**: Make tools idempotent where possible
5. **Documentation**: Provide clear descriptions and examples
6. **Versioning**: Version your tools for backward compatibility
7. **Rate Limiting**: Implement appropriate rate limits
8. **Monitoring**: Track tool usage and performance

## Troubleshooting

### Common Issues

#### Tool Not Found
```json
{
  "error": "Tool not found",
  "code": "TOOL_NOT_FOUND",
  "tool": "account.invalid"
}
```
**Solution**: Check tool name and ensure it's registered.

#### Invalid Parameters
```json
{
  "error": "Invalid parameters",
  "code": "INVALID_PARAMS",
  "details": {
    "missing": ["account_uuid"],
    "invalid": []
  }
}
```
**Solution**: Provide all required parameters per schema.

#### Permission Denied
```json
{
  "error": "Permission denied",
  "code": "FORBIDDEN",
  "tool": "payment.transfer"
}
```
**Solution**: Ensure user has necessary permissions.

## Resources

- [MCP Specification](https://modelcontextprotocol.io/docs)
- [FinAegis API Documentation](../04-API/README.md)
- [Security Guide](SECURITY.md)
- [Agent Development](AGENT_DEVELOPMENT.md)
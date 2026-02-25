# x402 Protocol Implementation

## Status
- **Planned for**: v5.2.0
- **Design documents**: `docs/designs3/x402-implementation-plan.md`, `docs/designs3/x402-mobile-handover.md`
- **Technical reference**: `docs/designs3/x402-protocol-technical-reference.md`
- **Protocol version**: x402 v2 (CAIP-2 network identifiers)

## What is x402?
HTTP-native micropayment protocol using HTTP 402 status code. Enables per-request API monetization using USDC stablecoins on Base L2. Originally by Coinbase, open standard.

## Architecture
- **Resource Server mode**: FinAegis charges for premium API endpoints
- **Client mode**: AI agents pay external x402-enabled APIs
- **Facilitator proxy**: Verify/settle EIP-3009 signed payments on-chain

## New Domain: `App\Domain\X402`
- Services: X402FacilitatorService, X402PaymentVerificationService, X402SettlementService, X402ClientService, X402PricingService, X402HeaderCodecService, X402EIP712SignerService
- Middleware: X402PaymentGateMiddleware
- Models: X402Payment, X402MonetizedEndpoint, X402SpendingLimit
- Config: `config/x402.php`

## Key Integration Points
- `AgentProtocol/AgentPaymentIntegrationService` — Payment recording
- `AgentProtocol/DigitalSignatureService` — EIP-712 signing
- `Relayer/GasStationService` — Settlement gas sponsoring
- `AI/MCP/ToolRegistry` — Monetized tool tagging
- `AI/MCP/MCPServer` — 402 handling in tool execution
- `KeyManagement` — Secure facilitator key storage

## HTTP Headers
- `PAYMENT-REQUIRED` (server → client, base64 JSON)
- `PAYMENT-SIGNATURE` (client → server, base64 JSON)
- `PAYMENT-RESPONSE` (server → client, base64 JSON)

## Networks
- Base Mainnet: `eip155:8453` (production)
- Base Sepolia: `eip155:84532` (testnet)
- USDC Base: `0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913`

## Mobile App Impact
- HTTP 402 interceptor in Axios
- EIP-3009 wallet signing (ethers.js)
- Payment approval bottom sheet modal
- Agent spending limits settings
- Micropayment activity feed grouping

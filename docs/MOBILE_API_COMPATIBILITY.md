# Mobile API Compatibility -- v2.10.0

Handover document for the mobile team (Expo/React Native, separate repo).

---

## 1. Overview

v2.10.0 adds approximately 30 mobile-facing API endpoints across 4 PRs. Key conventions:

- **Response envelope**: All endpoints return `{ "success": true, "data": {...} }` consistently.
- **Authentication**: Sanctum token-based. Login returns the token in `data.access_token`.
- **Base URL**: All endpoints are prefixed with `/api` unless otherwise noted.

---

## 2. Authentication

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/auth/login` | POST | No | Returns `{ success: true, data: { user, access_token, token_type } }` |
| `/auth/register` | POST | No | Register a new user account |
| `/auth/me` | GET | Yes | Returns user profile (alias of `/auth/user`) |
| `/auth/delete-account` | POST | Yes | Soft deletes the user account |
| `/auth/passkey/register` | POST | Yes | Register a passkey for the authenticated user |
| `/auth/passkey/challenge` | POST | No | Request a passkey authentication challenge |
| `/auth/passkey/verify` | POST | No | Verify a passkey authentication response |
| `/v1/auth/passkey/challenge` | POST | No | v1 prefix alias for passkey challenge |
| `/v1/auth/passkey/authenticate` | POST | No | v1 prefix alias for passkey verify |

---

## 3. Wallet API

**Prefix**: `/api/v1/wallet/`
**Auth**: All endpoints require `auth:sanctum`.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/tokens` | GET | List supported tokens (USDC, USDT, WETH, WBTC) with network and decimals info |
| `/balances` | GET | ERC-20 balances across the user's smart accounts |
| `/state` | GET | Aggregate of balances, addresses, and sync info |
| `/addresses` | GET | List user's smart account addresses per network |
| `/transactions` | GET | Cursor-based transaction history. Query params: `?cursor=X&limit=Y` |
| `/transactions/{id}` | GET | Single transaction detail |
| `/transactions/send` | POST | Create and auto-submit a payment intent. Body: `{ to, amount, asset, network }` |

---

## 4. TrustCert API

**Prefix**: `/api/v1/trustcert/`
**Auth**: All endpoints require `auth:sanctum`.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/current` | GET | User's current trust level and certificate info |
| `/requirements` | GET | All trust levels with their requirements |
| `/requirements/{level}` | GET | Requirements for a specific trust level |
| `/limits` | GET | Transaction limits per trust level |
| `/check-limit` | POST | Check if an amount is within the user's limits. Body: `{ amount, transaction_type }` |
| `/applications` | POST | Create a certificate application. Body: `{ target_level }` |
| `/applications/current` | GET | Current active application |
| `/applications/{id}` | GET | Application by ID |
| `/applications/{id}/documents` | POST | Upload a document. Body: `{ document_type, file_name }` |
| `/applications/{id}/submit` | POST | Submit application for review |
| `/applications/{id}/cancel` | POST | Cancel a pending application |

**Trust levels**: `unknown`, `basic`, `verified`, `high`, `ultimate`

---

## 5. Commerce API

**Prefix**: `/api/v1/commerce/`
**Auth**: All endpoints require `auth:sanctum`.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/merchants` | GET | List available merchants |
| `/parse-qr` | POST | Parse a merchant QR code. Body: `{ qr_data }` |
| `/payment-requests` | POST | Create a payment request. Body: `{ merchant_id, amount, asset, network }` |
| `/payments` | POST | Process a payment. Body: `{ payment_request_id }` |
| `/generate-qr` | POST | Generate a payment QR code. Body: `{ amount, asset, network }` |

**QR format**: `finaegis://pay?merchant=X&amount=Y&asset=Z&network=N`

---

## 6. Relayer API

**Prefix**: `/api/v1/relayer/`
**Auth**: All endpoints require `auth:sanctum`.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/status` | GET | Relayer health and gas prices per network |
| `/estimate-gas` | POST | Estimate gas. Body: `{ network, to, data? }` |
| `/build-userop` | POST | Build a UserOperation. Body: `{ network, to, value?, data? }` |
| `/submit` | POST | Submit a signed UserOp. Body: `{ network, user_op, signature }` |
| `/userop/{hash}` | GET | UserOp status by hash |
| `/supported-tokens` | GET | Tokens accepted for gas payment |
| `/paymaster-data` | GET | Paymaster configuration per network |

**Supported networks**: `polygon`, `arbitrum`, `optimism`, `base`, `ethereum`

---

## 7. Existing Endpoints (unchanged, already working)

These endpoints were available before v2.10.0 and remain unchanged:

- **Mobile device management**: `/api/mobile/devices/*`
- **Biometric auth**: `/api/mobile/auth/biometric/*`
- **Push notifications**: `/api/mobile/notifications/*`
- **Payment intents**: `/api/v1/payments/intents/*`
- **Activity feed**: `/api/v1/activity`
- **Transaction details**: `/api/v1/transactions/{txId}`
- **Receipt**: GET/POST `/api/v1/transactions/{txId}/receipt`
- **Network status**: `/api/v1/networks/status`, `/api/v1/networks/{network}/status`
- **Wallet receive**: `/api/v1/wallet/receive`
- **Wallet transfer helpers**: `/api/v1/wallet/validate-address`, `/api/v1/wallet/resolve-name`, `/api/v1/wallet/quote`
- **Cards**: `/api/v1/cards/*`
- **Relayer (existing)**: `/api/v1/relayer/networks`, `/api/v1/relayer/sponsor`, `/api/v1/relayer/estimate`
- **Smart accounts**: `/api/v1/relayer/account`, `/api/v1/relayer/accounts`
- **TrustCert presentations**: `/api/v1/trustcert/{certId}/*`
- **Privacy**: `/api/v1/privacy/*`
- **RegTech**: `/api/regtech/*`

---

## 8. Error Format

All errors follow a consistent envelope:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message."
  }
}
```

**HTTP status codes**:
- `404` -- Not found
- `409` -- Conflict
- `422` -- Validation error

---

## 9. CORS

The following custom headers are allowed in CORS configuration:

- `X-Client-Platform`
- `X-Client-Version`

---

## 10. Known Issues

- **Cache permissions**: Run `sudo chown -R www-data:www-data storage/framework/cache/data/` if you encounter permission errors on the server.
- **Demo mode**: Commerce merchants and some wallet data are demo/placeholder values.

---

## 11. What Mobile Should Update

The following are breaking or notable changes that the mobile app should account for:

- **Auth envelope change**: Auth endpoints now return `{ success, data }` envelope. Previously `/auth/login` and `/auth/user` returned flat objects.
- **Passkey path aliases**: Passkey endpoints now work at both `/api/auth/passkey/*` and `/api/v1/auth/passkey/*`.
- **Receipt GET endpoint**: `GET /api/v1/transactions/{txId}/receipt` is now available. Previously only POST was supported.
- **Parameterized network status**: Network status now supports a parameterized path: `/api/v1/networks/{network}/status`.

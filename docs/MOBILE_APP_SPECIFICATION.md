# FinAegis Mobile Wallet - Technical Specification

> **Version Context:** This specification was written for the v2.5.0 mobile app launch.
> The FinAegis backend has since evolved to v5.0.0 with 41 domains, including significant
> additions such as CrossChain, DeFi, RegTech, Event Streaming, GraphQL API, Plugin
> Marketplace, and more. Mobile-relevant backend domains (Mobile, MobilePayment, Relayer,
> Commerce, TrustCert, Privacy, KeyManagement) have all received further enhancements in
> subsequent releases. See [CLAUDE.md](../CLAUDE.md) for the current version status and
> full domain listing.

**Version**: 1.4
**Date**: February 2, 2026
**Status**: Backend Complete - Ready for Mobile Development
**Target Release**: v2.5.1 (Mobile App Launch)

---

## Executive Summary

FinAegis Mobile is a next-generation embedded wallet application combining traditional banking convenience with blockchain-native privacy and compliance features. Inspired by Privy and Turnkey, it provides non-custodial key management with enterprise-grade security.

### Unique Value Propositions

| Feature | Description | Differentiator |
|---------|-------------|----------------|
| **Stablecoin Commerce** | Pay at physical/online shops with stablecoins | Fiat-like UX with crypto rails |
| **Privacy Layer** | Untraceable public transactions with fraud investigation capability | RAILGUN-inspired Proof of Innocence |
| **TrustCert Attestations** | Blockchain-verified enhanced KYC certificates | Immutable, expirable, verifiable credentials |

---

## 1. Product Vision

### 1.1 Target Users

| Persona | Use Case | Key Needs |
|---------|----------|-----------|
| **Retail Consumer** | Daily payments, savings | Simple UX, low fees, privacy |
| **Business Owner** | Accept crypto payments | POS integration, instant settlement |
| **High-Net-Worth Individual** | Asset management | Privacy, multi-sig, hardware wallet |
| **Enterprise/Government Vendor** | Dual-use goods trade | Enhanced verification, audit trail |

### 1.2 Core Principles

1. **Self-Custody First**: Users control their keys (Shamir sharding)
2. **Privacy by Default**: Transactions private unless disclosure required
3. **Compliance Ready**: Proof of Innocence, not surveillance
4. **Regulatory Friendly**: Works with institutions, not against them

---

## 2. Feature Specifications

### 2.1 Stablecoin Commerce

#### 2.1.1 Overview

Enable users to pay at participating merchants using stablecoins (USDC, USDT, DAI, or FinAegis-issued stablecoins) with a UX identical to traditional card payments.

#### 2.1.2 Payment Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    STABLECOIN PAYMENT FLOW                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. SCAN                    2. CONFIRM                           │
│  ┌─────────────┐           ┌─────────────────────┐              │
│  │   ┌───┐     │           │  Pay €45.00         │              │
│  │   │QR │     │    →      │  to: Coffee Shop    │              │
│  │   └───┘     │           │  ───────────────    │              │
│  │  Scan Code  │           │  [USDC] €45.00      │              │
│  └─────────────┘           │  Fee: €0.02         │              │
│                            │  ═══════════════    │              │
│                            │  [👆 Pay with Face] │              │
│                            └─────────────────────┘              │
│                                                                  │
│  3. SIGN (Privacy Layer)   4. CONFIRM                           │
│  ┌─────────────────────┐   ┌─────────────────────┐              │
│  │  🔒 Private Tx       │   │  ✓ Payment Sent     │              │
│  │  Shielding...       │   │                     │              │
│  │  ████████░░ 80%     │   │  Ref: FA-2026-XXXX  │              │
│  └─────────────────────┘   │  [View Receipt]     │              │
│                            └─────────────────────┘              │
└─────────────────────────────────────────────────────────────────┘
```

#### 2.1.3 Technical Components

| Component | Implementation | Status |
|-----------|---------------|--------|
| **QR Code Standard** | EIP-681 / BIP-21 extended | New |
| **Payment Protocol** | EIP-712 typed signatures | New |
| **Stablecoin Support** | USDC, USDT, DAI, FA-USD | Existing + New |
| **Gas Abstraction** | EIP-4337 Account Abstraction | New |
| **Fiat Conversion** | Real-time oracle pricing | Existing |
| **Merchant SDK** | TypeScript/React Native | New |

#### 2.1.4 Merchant Integration

```typescript
// Merchant SDK - Payment Request
interface PaymentRequest {
  merchantId: string;           // FinAegis merchant ID
  amount: string;               // Amount in fiat (e.g., "45.00")
  currency: 'EUR' | 'USD' | 'GBP';
  acceptedTokens: string[];     // ['USDC', 'USDT', 'DAI']
  callbackUrl: string;          // Webhook for confirmation
  metadata: {
    orderId: string;
    description: string;
  };
}

// QR Code Payload
interface QRPayload {
  protocol: 'finaegis';
  version: 1;
  request: PaymentRequest;
  signature: string;            // Merchant signature
  expiresAt: number;            // Unix timestamp
}
```

#### 2.1.5 Settlement Options

| Mode | Speed | Fee | Use Case |
|------|-------|-----|----------|
| **Instant (L2)** | <2 seconds | 0.1% | Small purchases |
| **Batched (L1)** | ~15 minutes | 0.05% | Large settlements |
| **Privacy Shield** | ~30 seconds | 0.3% | Privacy-required |

---

### 2.2 Privacy Layer

#### 2.2.1 Overview

Implement a privacy system where:
- **Public**: Transactions are unlinkable (no address correlation)
- **Private**: Full audit trail for authorized fraud investigations
- **Compliant**: Proof of Innocence without revealing transaction history

#### 2.2.2 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     PRIVACY LAYER ARCHITECTURE                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │   USER WALLET    │    │  SHIELD POOL     │                   │
│  │  ┌────────────┐  │    │  ┌────────────┐  │                   │
│  │  │ 100 USDC   │──┼────┼─▶│ Encrypted  │  │                   │
│  │  │ (visible)  │  │    │  │   UTXOs    │  │                   │
│  │  └────────────┘  │    │  └────────────┘  │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                   │                              │
│           ┌───────────────────────┼───────────────────────┐     │
│           │                       ▼                       │     │
│           │  ┌─────────────────────────────────────────┐ │     │
│           │  │         ZK-SNARK PROVER                 │ │     │
│           │  │  ┌─────────────────────────────────┐    │ │     │
│           │  │  │ Proof: "I own 50 USDC in pool"  │    │ │     │
│           │  │  │ WITHOUT revealing:               │    │ │     │
│           │  │  │  - Source address               │    │ │     │
│           │  │  │  - Transaction history          │    │ │     │
│           │  │  │  - Total balance                │    │ │     │
│           │  │  └─────────────────────────────────┘    │ │     │
│           │  └─────────────────────────────────────────┘ │     │
│           │                       │                       │     │
│           │              PRIVACY LAYER                    │     │
│           └───────────────────────┼───────────────────────┘     │
│                                   ▼                              │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │   RECIPIENT      │    │  AUDIT VAULT     │                   │
│  │  ┌────────────┐  │    │  ┌────────────┐  │                   │
│  │  │ 50 USDC    │◀─┼────┼──│ Encrypted  │  │                   │
│  │  │ (received) │  │    │  │   Logs     │  │                   │
│  │  └────────────┘  │    │  └────────────┘  │                   │
│  └──────────────────┘    └──────────────────┘                   │
│                                   │                              │
│                          DECRYPT ONLY WITH:                      │
│                          - Court order                           │
│                          - Multi-sig (3-of-5 compliance)         │
│                          - User consent                          │
└─────────────────────────────────────────────────────────────────┘
```

#### 2.2.3 Privacy Modes

| Mode | Public Visibility | Audit Access | Use Case |
|------|-------------------|--------------|----------|
| **Transparent** | Full | Full | Regulatory reporting |
| **Shielded** | None (ZK proof only) | Encrypted logs | Personal privacy |
| **Selective** | Chosen fields only | Partial | Business compliance |

#### 2.2.4 Proof of Innocence

Users can generate cryptographic proofs that their funds:
- Do NOT originate from sanctioned addresses (OFAC, EU, UN)
- Were NOT involved in known hacks/exploits
- Meet specific compliance criteria

```typescript
interface ProofOfInnocence {
  proofType: 'SANCTIONS' | 'ORIGIN' | 'COMPLIANCE';
  generatedAt: Date;
  expiresAt: Date;
  proof: string;              // ZK-SNARK proof
  publicInputs: {
    sanctionsListHash: string;
    complianceLevel: 'BASIC' | 'ENHANCED' | 'FULL';
  };
  // Verifiable on-chain or off-chain
}
```

#### 2.2.5 Audit Vault Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                       AUDIT VAULT                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ENCRYPTION: AES-256-GCM + Shamir's Secret Sharing (5 shares)   │
│                                                                  │
│  KEY HOLDERS (3-of-5 required):                                  │
│  ├── FinAegis Compliance Officer                                │
│  ├── External Auditor (Big 4)                                   │
│  ├── Legal Counsel                                               │
│  ├── Regulatory Body Representative                              │
│  └── User Recovery Key (optional)                                │
│                                                                  │
│  STORED DATA (encrypted):                                        │
│  ├── Transaction ID                                              │
│  ├── Sender address                                              │
│  ├── Recipient address                                           │
│  ├── Amount                                                      │
│  ├── Timestamp                                                   │
│  ├── IP address (hashed)                                         │
│  └── Device fingerprint                                          │
│                                                                  │
│  ACCESS TRIGGERS:                                                │
│  ├── Court order with case number                                │
│  ├── Regulatory investigation (documented)                       │
│  ├── User-initiated disclosure                                   │
│  └── Fraud threshold exceeded (automatic flag)                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### 2.3 TrustCert - Enhanced KYC Attestations

#### 2.3.1 Overview

TrustCert is a blockchain-based certificate system that provides:
- **Enhanced Verification**: Beyond standard KYC (source of funds, beneficial ownership, etc.)
- **Immutable Proof**: On-chain attestation that cannot be falsified
- **Expirable**: Certificates have validity periods
- **Verifiable**: Anyone can verify without accessing underlying data

#### 2.3.2 Use Cases

| Certificate Type | Verification Level | Validity | Use Case |
|-----------------|-------------------|----------|----------|
| **Personal Trust** | Enhanced KYC | 1 year | High-value transactions |
| **Business Trust** | Full KYB + Beneficial Ownership | 2 years | B2B transactions |
| **Dual-Use Export** | Enhanced + Government checks | 6 months | Controlled goods trade |
| **Accredited Investor** | Financial verification | 1 year | Investment access |
| **White Hat** | Technical + Background check | 1 year | Security research |

#### 2.3.3 Certificate Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    TRUSTCERT ARCHITECTURE                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                  ON-CHAIN (Soulbound Token)              │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │  Token ID: 0x1234...                            │    │    │
│  │  │  Owner: 0xUserWallet...                         │    │    │
│  │  │  Type: BUSINESS_TRUST                           │    │    │
│  │  │  Issuer: 0xFinAegis...                          │    │    │
│  │  │  IssuedAt: 2026-02-01                           │    │    │
│  │  │  ExpiresAt: 2028-02-01                          │    │    │
│  │  │  CredentialHash: 0xabcd...                      │    │    │
│  │  │  Status: ACTIVE                                 │    │    │
│  │  │  Revocable: true                                │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  │                         │                                │    │
│  │                         │ Verifiable Credential          │    │
│  │                         ▼                                │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                  OFF-CHAIN (Encrypted Storage)           │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │  Full Name: [ENCRYPTED]                         │    │    │
│  │  │  Company: [ENCRYPTED]                           │    │    │
│  │  │  Verification Documents: [ENCRYPTED]            │    │    │
│  │  │  Beneficial Owners: [ENCRYPTED]                 │    │    │
│  │  │  Source of Funds: [ENCRYPTED]                   │    │    │
│  │  │  Background Check: [ENCRYPTED]                  │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  │                                                          │    │
│  │  Decryption: Requires user consent + FinAegis key        │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  VERIFICATION FLOW:                                              │
│  1. Verifier requests proof                                      │
│  2. User generates ZK proof from SBT                             │
│  3. Proof confirms: "Valid BUSINESS_TRUST cert, not expired"     │
│  4. No PII disclosed                                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 2.3.4 Smart Contract Interface

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

interface ITrustCert {
    enum CertType {
        PERSONAL_TRUST,
        BUSINESS_TRUST,
        DUAL_USE_EXPORT,
        ACCREDITED_INVESTOR,
        WHITE_HAT
    }

    enum Status { PENDING, ACTIVE, SUSPENDED, REVOKED, EXPIRED }

    struct Certificate {
        uint256 tokenId;
        address holder;
        CertType certType;
        uint256 issuedAt;
        uint256 expiresAt;
        bytes32 credentialHash;    // Hash of off-chain data
        Status status;
        string metadataURI;        // IPFS/Arweave link
    }

    // Issue certificate (only authorized issuer)
    function issue(
        address to,
        CertType certType,
        uint256 validityDays,
        bytes32 credentialHash
    ) external returns (uint256 tokenId);

    // Revoke certificate (issuer or holder)
    function revoke(uint256 tokenId, string calldata reason) external;

    // Verify certificate validity
    function verify(uint256 tokenId) external view returns (
        bool isValid,
        CertType certType,
        uint256 expiresAt
    );

    // Generate ZK proof of certificate ownership
    function generateProof(
        uint256 tokenId,
        bytes calldata proofRequest
    ) external view returns (bytes memory proof);

    // Verify ZK proof (can be called by anyone)
    function verifyProof(
        bytes calldata proof,
        bytes calldata publicInputs
    ) external view returns (bool);

    // Soulbound: transfers are disabled
    function transferFrom(address, address, uint256) external pure {
        revert("TrustCert: Soulbound - transfers disabled");
    }
}
```

#### 2.3.5 Verification Process

```
┌─────────────────────────────────────────────────────────────────┐
│               TRUSTCERT ISSUANCE WORKFLOW                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  STEP 1: APPLICATION                                             │
│  ├── User selects certificate type                               │
│  ├── Uploads required documents                                  │
│  ├── Pays verification fee (crypto/fiat)                         │
│  └── Signs consent for background check                          │
│                                                                  │
│  STEP 2: VERIFICATION (7-14 days)                                │
│  ├── Document verification (AI + Human review)                   │
│  ├── Identity verification (Liveness + Document match)           │
│  ├── Background checks (PEP, Sanctions, Criminal)                │
│  ├── Source of funds verification                                │
│  └── Beneficial ownership discovery                              │
│                                                                  │
│  STEP 3: ENHANCED CHECKS (for specific types)                    │
│  ├── DUAL_USE_EXPORT: Government database check                  │
│  ├── ACCREDITED_INVESTOR: Financial verification                 │
│  ├── WHITE_HAT: Technical assessment + references                │
│  └── BUSINESS_TRUST: Company registry + UBO verification         │
│                                                                  │
│  STEP 4: ISSUANCE                                                │
│  ├── Generate credential hash                                    │
│  ├── Mint Soulbound Token to user wallet                         │
│  ├── Store encrypted data off-chain                              │
│  └── Emit CertificateIssued event                                │
│                                                                  │
│  STEP 5: ONGOING MONITORING                                      │
│  ├── Continuous sanctions screening                              │
│  ├── Adverse media monitoring                                    │
│  ├── Renewal reminders (30, 14, 7 days before expiry)            │
│  └── Auto-expiration at validity end                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Technical Architecture

### 3.1 Mobile App Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                    MOBILE APP ARCHITECTURE                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    PRESENTATION LAYER                    │    │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐    │    │
│  │  │  Home   │  │ Wallet  │  │   Pay   │  │ Profile │    │    │
│  │  └─────────┘  └─────────┘  └─────────┘  └─────────┘    │    │
│  │                                                          │    │
│  │  Framework: Expo SDK 52 (React Native)                   │    │
│  │  UI: NativeWind (Tailwind CSS)                           │    │
│  │  Navigation: Expo Router (file-based)                    │    │
│  │  Animation: Reanimated 3                                 │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    STATE MANAGEMENT                       │    │
│  │  ┌─────────────────┐  ┌─────────────────────────────┐   │    │
│  │  │     Zustand     │  │     TanStack Query          │   │    │
│  │  │  (Local State)  │  │  (Server State + Cache)     │   │    │
│  │  └─────────────────┘  └─────────────────────────────┘   │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    SECURITY LAYER                         │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │    │
│  │  │   Passkeys  │  │  Biometric  │  │   Secure    │      │    │
│  │  │   (FIDO2)   │  │   (P-256)   │  │   Enclave   │      │    │
│  │  └─────────────┘  └─────────────┘  └─────────────┘      │    │
│  │                                                          │    │
│  │  Key Storage: expo-secure-store + Keychain/Keystore      │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    WALLET ENGINE                          │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │              KEY MANAGEMENT                       │    │    │
│  │  │  ┌───────────┐ ┌───────────┐ ┌───────────┐      │    │    │
│  │  │  │  Device   │ │   Auth    │ │ Recovery  │      │    │    │
│  │  │  │  Shard    │ │  Shard    │ │  Shard    │      │    │    │
│  │  │  │ (Enclave) │ │  (HSM)    │ │ (Cloud)   │      │    │    │
│  │  │  └───────────┘ └───────────┘ └───────────┘      │    │    │
│  │  │           Shamir's Secret Sharing (2-of-3)       │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  │                                                          │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │              BLOCKCHAIN CLIENTS                   │    │    │
│  │  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌───────┐ │    │    │
│  │  │  │Ethereum │ │ Polygon │ │   BSC   │ │Bitcoin│ │    │    │
│  │  │  │ (ethers)│ │ (ethers)│ │ (ethers)│ │(bitcoinjs)│  │    │
│  │  │  └─────────┘ └─────────┘ └─────────┘ └───────┘ │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  │                                                          │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │              PRIVACY ENGINE (NATIVE)             │    │    │
│  │  │  ┌─────────────────┐  ┌─────────────────┐       │    │    │
│  │  │  │   ZK Prover     │  │  Shield Pool    │       │    │    │
│  │  │  │  (Rust/C++ via  │  │   Interface     │       │    │    │
│  │  │  │   JSI/FFI)      │  │                 │       │    │    │
│  │  │  │  ⚠️ BACKGROUND   │  │                 │       │    │    │
│  │  │  │    THREAD ONLY  │  │                 │       │    │    │
│  │  │  └─────────────────┘  └─────────────────┘       │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  │                                                          │    │
│  │  ┌─────────────────────────────────────────────────┐    │    │
│  │  │              GAS ABSTRACTION                     │    │    │
│  │  │  ┌─────────────────────────────────────────┐    │    │    │
│  │  │  │  ERC-4337 Account Abstraction           │    │    │    │
│  │  │  │  • UserOperation signing                │    │    │    │
│  │  │  │  • Paymaster integration                │    │    │    │
│  │  │  │  • Fee payment in stablecoins           │    │    │    │
│  │  │  └─────────────────────────────────────────┘    │    │    │
│  │  └─────────────────────────────────────────────────┘    │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    NETWORK LAYER                          │    │
│  │  ┌─────────────────┐  ┌─────────────────────────────┐   │    │
│  │  │   REST API      │  │     WebSocket (Soketi)      │   │    │
│  │  │   (Axios)       │  │   (Real-time updates)       │   │    │
│  │  └─────────────────┘  └─────────────────────────────┘   │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Key Management (Shamir's Secret Sharing)

```typescript
// Key Sharding Implementation
interface KeyShardingConfig {
  totalShards: 3;
  threshold: 2;        // 2-of-3 required
  algorithm: 'shamir';
  encryptionCurve: 'secp256k1';
}

interface KeyShards {
  deviceShard: {
    storage: 'secure-enclave';     // iOS Keychain / Android Keystore
    encryption: 'AES-256-GCM';
    biometricProtected: true;
  };
  authShard: {
    storage: 'backend-hsm';        // FinAegis HSM
    retrieval: 'authenticated-api';
    sessionBound: true;
  };
  recoveryShard: {
    storage: 'encrypted-cloud';    // User's iCloud/Google Drive
    encryption: 'user-password-derived';
    optional: true;
  };
}

// Signing Flow
async function signTransaction(tx: Transaction): Promise<SignedTransaction> {
  // 1. Get device shard (biometric auth required)
  const deviceShard = await secureEnclave.getShard({
    biometric: true,
    reason: 'Authorize transaction'
  });

  // 2. Get auth shard from backend
  const authShard = await api.getAuthShard({
    sessionToken: currentSession.token,
    transactionHash: tx.hash
  });

  // 3. Reconstruct key in memory (never persisted)
  const privateKey = shamirs.combine([deviceShard, authShard]);

  // 4. Sign transaction
  const signature = await sign(tx, privateKey);

  // 5. Immediately clear key from memory
  privateKey.fill(0);

  return { tx, signature };
}
```

### 3.2.1 Passkey/WebAuthn Integration (Passwordless Authentication)

> **Requirement**: Modern "Privy/Turnkey-like" experience without seed phrases.
> **Implementation**: FIDO2/WebAuthn with Secure Enclave cryptographic signing.

#### Architecture Decision: Passkey-First Authentication

**Why Passkeys over Traditional Auth:**
- No passwords to phish or leak
- No seed phrases for users to manage
- Biometric protection via hardware (Secure Enclave / StrongBox)
- Phishing-resistant (origin-bound credentials)
- Cross-device sync via iCloud Keychain / Google Password Manager

#### Implementation Flow

```typescript
// 1. Registration (Passkey Creation)
interface PasskeyRegistration {
  challenge: string;              // Server-generated challenge
  rpId: 'finaegis.com';           // Relying Party ID (domain-bound)
  rpName: 'FinAegis Wallet';
  user: {
    id: string;                   // User UUID
    name: string;                 // Email/username
    displayName: string;
  };
  pubKeyCredParams: [
    { type: 'public-key', alg: -7 },   // ES256 (P-256)
    { type: 'public-key', alg: -257 }  // RS256 fallback
  ];
  authenticatorSelection: {
    authenticatorAttachment: 'platform';  // Prefer built-in (Face ID, etc.)
    userVerification: 'required';
    residentKey: 'required';
  };
}

// 2. Authentication (Signing)
async function authenticateWithPasskey(): Promise<AuthResult> {
  // Browser/Native WebAuthn API
  const credential = await navigator.credentials.get({
    publicKey: {
      challenge: serverChallenge,
      rpId: 'finaegis.com',
      userVerification: 'required',
      timeout: 60000,
    }
  });

  // Send to backend for verification
  return api.verifyPasskey({
    credentialId: credential.id,
    clientDataJSON: credential.response.clientDataJSON,
    authenticatorData: credential.response.authenticatorData,
    signature: credential.response.signature,
  });
}

// 3. Transaction Signing with Passkey
async function signWithPasskey(tx: Transaction): Promise<SignedTransaction> {
  // Passkey signature serves as 2FA for shard retrieval
  const passkeyAuth = await authenticateWithPasskey();

  // Now retrieve auth shard (backend verifies passkey signature)
  const authShard = await api.getAuthShard({
    passkeyAuthToken: passkeyAuth.token,
    transactionHash: tx.hash,
  });

  // Combine with device shard (biometric-protected)
  const privateKey = shamirs.combine([deviceShard, authShard]);
  return sign(tx, privateKey);
}
```

#### Mobile Implementation

| Platform | Library | Secure Hardware |
|----------|---------|-----------------|
| **iOS** | `ASAuthorizationController` | Secure Enclave (P-256) |
| **Android** | `Fido2ApiClient` | StrongBox / TEE |
| **React Native** | `react-native-passkey` | Platform-specific |

#### Backend Integration

```php
// Backend Passkey Verification (WebAuthn)
class PasskeyController extends Controller
{
    public function verify(PasskeyVerifyRequest $request): JsonResponse
    {
        $credential = $this->webAuthnService->verify(
            credentialId: $request->credential_id,
            clientDataJSON: $request->client_data_json,
            authenticatorData: $request->authenticator_data,
            signature: $request->signature,
            challenge: session('webauthn_challenge'),
        );

        if (!$credential->isValid()) {
            return response()->json(['error' => 'Invalid passkey'], 401);
        }

        // Issue short-lived token for shard retrieval
        return response()->json([
            'token' => $this->tokenService->issueShardRetrievalToken(
                userId: $credential->userId,
                ttl: 60, // 60 seconds
            ),
        ]);
    }
}
```

### 3.3 Privacy Layer Integration

#### Privacy Protocol Decision: RAILGUN-Inspired Approach

> **Product Decision**: FinAegis implements a **RAILGUN-inspired** privacy protocol, NOT Tornado Cash.

**Why RAILGUN over Tornado Cash:**

| Factor | Tornado Cash | RAILGUN (FinAegis Choice) |
|--------|--------------|---------------------------|
| **Regulatory Status** | OFAC sanctioned | Compliant (Proof of Innocence) |
| **Fund Tracing** | No audit capability | Encrypted audit vault |
| **User Protection** | Anonymity only | Anonymity + Compliance |
| **On-chain Footprint** | Mixer contract | UTXO-based shielded pool |
| **Proof System** | Tornado's snark | Groth16 / PLONK |

**Key Differentiators:**
1. **Proof of Innocence**: Users can prove funds are NOT from sanctioned sources without revealing transaction history
2. **Encrypted Audit Vault**: Transaction details encrypted with multi-sig (3-of-5) for lawful disclosure
3. **Selective Disclosure**: Users choose what to reveal for compliance (KYC level, transaction count, etc.)
4. **Compliant by Design**: Works with regulators, not against them

```typescript
// Privacy Transaction Flow
interface PrivacyTransaction {
  type: 'SHIELD' | 'UNSHIELD' | 'TRANSFER';
  amount: string;
  token: string;
  recipient?: string;        // Only for TRANSFER/UNSHIELD
  privacyLevel: 'FULL' | 'SELECTIVE';
  auditConsent: boolean;     // Required for compliance
}

async function executePrivacyTransaction(
  tx: PrivacyTransaction
): Promise<PrivacyTransactionResult> {
  // 1. Generate ZK proof for transaction validity
  const proof = await zkProver.generateProof({
    type: tx.type,
    amount: tx.amount,
    publicInputs: {
      token: tx.token,
      shieldPoolAddress: SHIELD_POOL_ADDRESS,
    },
    privateInputs: {
      balance: await getShieldedBalance(),
      nullifier: generateNullifier(),
    }
  });

  // 2. Create audit log (encrypted)
  const auditLog = await createAuditLog({
    transaction: tx,
    proof: proof.publicSignals,
    timestamp: Date.now(),
    deviceId: deviceId,
  });

  // 3. Submit to privacy pool
  const result = await privacyPool.execute({
    proof: proof.proof,
    publicSignals: proof.publicSignals,
    auditLogHash: auditLog.hash,
  });

  return result;
}
```

### 3.4 Card Issuance & Tokenization (Apple/Google Pay)

> **Requirement**: Users must be able to tap-to-pay using stablecoins at regular retail shops.
> **Implementation**: Just-In-Time (JIT) Funding via Virtual Card with Push Provisioning.

#### 3.4.1 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CARD PAYMENT ARCHITECTURE                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────┐    ┌──────────────┐    ┌─────────────────┐            │
│  │  Mobile App │    │ Card Issuer  │    │  Card Network   │            │
│  │  (Wallet)   │    │ (Marqeta/    │    │ (Visa/MC)       │            │
│  │             │    │  Lithic)     │    │                 │            │
│  └──────┬──────┘    └──────┬───────┘    └────────┬────────┘            │
│         │                  │                      │                     │
│    1. Provision Card       │                      │                     │
│    ─────────────────►      │                      │                     │
│         │                  │                      │                     │
│    2. Add to Apple/Google  │                      │                     │
│       Wallet               │                      │                     │
│    ◄───────────────────────│                      │                     │
│         │                  │                      │                     │
│         │              ════════════════════       │                     │
│         │              USER TAPS AT SHOP          │                     │
│         │              ════════════════════       │                     │
│         │                  │                      │                     │
│         │    3. Authorization Request             │                     │
│         │         ◄───────────────────────────────│                     │
│         │                  │                      │                     │
│  ┌──────▼──────┐           │                      │                     │
│  │  FinAegis   │    4. JIT Webhook               │                     │
│  │   Backend   │◄──────────┤                      │                     │
│  │             │           │                      │                     │
│  │  Lock USDC  │───────────►                      │                     │
│  │  for $50    │    5. Approve                    │                     │
│  └─────────────┘           │──────────────────────►                     │
│                            │    6. Transaction OK │                     │
│                                                                           │
│  LATENCY BUDGET: < 2000ms (Authorization → Approval)                     │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 3.4.2 Provisioning Flow (Push to Wallet)

```typescript
// 1. User clicks "Add to Apple Wallet"
interface ProvisioningRequest {
  deviceId: string;
  walletType: 'APPLE_PAY' | 'GOOGLE_PAY';
  cardholderName: string;
}

// 2. Backend calls Card Issuer and Apple/Google
interface ProvisioningResponse {
  encryptedPassData: string;      // Do NOT decrypt on client
  activationData: string;
  ephemeralPublicKey: string;
  certificateChain: string[];
}

// 3. Pass directly to native APIs
// iOS: PKAddPaymentPassViewController
// Android: PushProvisioningClient

// CRITICAL: Never decrypt encryptedPassData on client
// The native wallet APIs handle decryption securely
```

#### 3.4.3 JIT Funding Webhook (Backend)

```php
// Real-time authorization decision (< 2000ms latency budget)
class JitFundingWebhookController
{
    public function authorize(JitFundingRequest $request): JsonResponse
    {
        $user = $this->getUserFromCard($request->card_token);
        $amount = Money::USD($request->amount);

        // 1. Check stablecoin balance
        $balance = $this->walletService->getBalance($user, 'USDC');

        if ($balance->lessThan($amount)) {
            return $this->deny('INSUFFICIENT_FUNDS');
        }

        // 2. Lock funds (create hold)
        $hold = $this->walletService->createHold($user, 'USDC', $amount, [
            'authorization_id' => $request->authorization_id,
            'merchant' => $request->merchant_name,
        ]);

        // 3. Approve transaction
        return response()->json([
            'approved' => true,
            'hold_id' => $hold->id,
        ]);
    }
}
```

#### 3.4.4 Supported Card Issuers

| Issuer | Type | Features | Status |
|--------|------|----------|--------|
| **Marqeta** | Virtual/Physical | JIT funding, Webhooks, Apple/Google Pay | Planned |
| **Lithic** | Virtual/Physical | API-first, Sandbox, Push provisioning | Planned |
| **Stripe Issuing** | Virtual | Simple API, Stripe ecosystem | Planned |

#### 3.4.5 Mobile Implementation

```typescript
// React Native implementation using native modules
import { ApplePayProvisioning } from '@finaegis/react-native-wallet-provisioning';

async function addCardToWallet() {
  // 1. Request provisioning data from backend
  const response = await api.post('/api/v1/cards/provision', {
    deviceId: await getDeviceId(),
    walletType: Platform.OS === 'ios' ? 'APPLE_PAY' : 'GOOGLE_PAY',
  });

  // 2. Pass DIRECTLY to native API (no decryption)
  if (Platform.OS === 'ios') {
    await ApplePayProvisioning.addCard({
      encryptedPassData: response.encryptedPassData,
      activationData: response.activationData,
      ephemeralPublicKey: response.ephemeralPublicKey,
    });
  } else {
    await GooglePayProvisioning.pushProvision(response);
  }
}
```

---

### 3.5 Privacy Shield (Client-Side ZK Proofs)

> **Requirement**: Transactions must be mathematically untraceable (Mixer/Pool).
> **Critical Constraint**: ZK proof generation is CPU-intensive and MUST NOT run on the main UI thread.

#### 3.5.1 Native Module Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PRIVACY SHIELD ARCHITECTURE                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                      REACT NATIVE LAYER                          │    │
│  │  ┌───────────────────────────────────────────────────────────┐  │    │
│  │  │                     JavaScript/TypeScript                   │  │    │
│  │  │  • UI Components (non-blocking)                            │  │    │
│  │  │  • State Management                                        │  │    │
│  │  │  • API Calls                                               │  │    │
│  │  └────────────────────────────┬──────────────────────────────┘  │    │
│  │                               │ JSI (JavaScript Interface)       │    │
│  │                               ▼                                  │    │
│  │  ┌───────────────────────────────────────────────────────────┐  │    │
│  │  │                    NATIVE BRIDGE                           │  │    │
│  │  │  • @finaegis/react-native-zk-prover                       │  │    │
│  │  │  • Runs on BACKGROUND THREAD                              │  │    │
│  │  │  • Progress callbacks to UI                                │  │    │
│  │  └────────────────────────────┬──────────────────────────────┘  │    │
│  └───────────────────────────────│──────────────────────────────────┘    │
│                                  │ FFI (Foreign Function Interface)      │
│                                  ▼                                       │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                      NATIVE LAYER (Rust/C++)                     │    │
│  │  ┌─────────────────────────────────────────────────────────┐    │    │
│  │  │  ZK Proof Generation Engine                              │    │    │
│  │  │  • Groth16 / Plonk prover                               │    │    │
│  │  │  • Merkle tree operations                                │    │    │
│  │  │  • Nullifier generation                                  │    │    │
│  │  │  • Compiled to .so (Android) / .a (iOS)                  │    │    │
│  │  └─────────────────────────────────────────────────────────┘    │    │
│  │                                                                   │    │
│  │  Performance Targets:                                            │    │
│  │  • iPhone 15 Pro: ~8-12 seconds                                  │    │
│  │  • iPhone 12: ~15-20 seconds                                     │    │
│  │  • Pixel 7: ~10-15 seconds                                       │    │
│  │  • Older devices: Use Delegated Proving                          │    │
│  └───────────────────────────────────────────────────────────────────┘    │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 3.5.2 Proof Generation Workflow

```typescript
// CRITICAL: This MUST run on a background thread via native module
import { ZkProver } from '@finaegis/react-native-zk-prover';

async function generatePrivacyProof(transaction: PrivacyTransaction): Promise<ZkProof> {
  // 1. Show UI progress indicator (non-blocking)
  setProofState({ status: 'generating', progress: 0 });

  // 2. Download latest Merkle tree root (~50kb)
  const merkleRoot = await api.get('/api/privacy/merkle-root');

  // 3. Generate proof on NATIVE BACKGROUND THREAD
  const proof = await ZkProver.generateProof({
    type: transaction.type,
    amount: transaction.amount,
    token: transaction.token,
    merkleRoot: merkleRoot.data,
    privateInputs: {
      balance: await getShieldedBalance(),
      nullifierSecret: await getSecureNullifier(),
    },
    // Progress callback runs on JS thread (non-blocking)
    onProgress: (progress) => setProofState({ status: 'generating', progress }),
  });

  // 4. Submit proof to relayer
  return proof;
}
```

#### 3.5.3 Delegated Proving (Fallback for Older Devices)

```typescript
// For devices that cannot generate proofs locally (< 4GB RAM)
interface DelegatedProofRequest {
  commitment: string;          // User's encrypted balance commitment
  blindingFactor: string;      // Encrypted with server's public key
  proofParameters: object;     // What to prove
}

// The server proves validity WITHOUT knowing the secret
// Privacy preserved through homomorphic encryption
async function requestDelegatedProof(params: DelegatedProofRequest): Promise<ZkProof> {
  const response = await api.post('/api/privacy/delegated-proof', params);
  return response.proof;
}
```

#### 3.5.4 Native Module Requirements

| Requirement | Implementation | Notes |
|-------------|----------------|-------|
| **Rust ZK Library** | `circom-compat` / `arkworks` | Compiled to iOS/Android |
| **JSI Bridge** | `react-native-jsi` | Zero-copy data transfer |
| **Background Thread** | Native thread pool | Avoid main thread |
| **Memory Management** | Manual in Rust | Prevent OOM crashes |
| **Progress Reporting** | Callback to JS | UI stays responsive |

---

### 3.6 Gas Abstraction (Meta-Transactions)

> **Requirement**: Users should NEVER need to hold ETH/MATIC to pay gas fees.
> **Implementation**: ERC-4337 Account Abstraction or Meta-Transaction Relayer.

#### 3.6.1 Gas Station Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    GAS ABSTRACTION ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────────┐                     ┌─────────────────────────┐    │
│  │   Mobile App    │                     │      Blockchain         │    │
│  │                 │                     │                         │    │
│  │  User has:      │                     │  User's Smart Wallet:   │    │
│  │  • 100 USDC     │                     │  • 100 USDC             │    │
│  │  • 0 ETH   ❌   │                     │  • 0 ETH                │    │
│  │                 │                     │                         │    │
│  │  Wants to send  │                     │                         │    │
│  │  50 USDC        │                     │                         │    │
│  └────────┬────────┘                     └──────────────┬──────────┘    │
│           │                                             │                │
│           │ 1. Sign UserOperation                       │                │
│           │    (no gas, just signature)                 │                │
│           ▼                                             │                │
│  ┌─────────────────────────────────────────────────────┐│                │
│  │                  RELAYER SERVICE                    ││                │
│  │                                                      ││                │
│  │  1. Receive signed UserOperation                    ││                │
│  │  2. Calculate gas cost in USDC                      ││                │
│  │     (e.g., $0.05 gas = 0.05 USDC)                   ││                │
│  │  3. Submit to bundler with Paymaster                ││                │
│  │  4. Paymaster pays gas in ETH                       ││                │
│  │  5. Deduct USDC fee from user's transfer            ││                │
│  │                                                      ││                │
│  └──────────────────────────────────────┬──────────────┘│                │
│                                         │               │                │
│                                         │ 2. Submit     │                │
│                                         │    Bundle     │                │
│                                         ▼               ▼                │
│                              ┌──────────────────────────────┐            │
│                              │        ERC-4337             │            │
│                              │    ENTRYPOINT CONTRACT      │            │
│                              │                              │            │
│                              │  • Validates UserOperation   │            │
│                              │  • Calls Paymaster           │            │
│                              │  • Executes transfer         │            │
│                              │                              │            │
│                              │  Result:                     │            │
│                              │  User sends 50 USDC          │            │
│                              │  Pays 0.05 USDC as "gas"     │            │
│                              │  Never needs ETH ✓           │            │
│                              └──────────────────────────────┘            │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 3.6.2 Backend Implementation

```php
// app/Domain/Relayer/Services/GasStationService.php
class GasStationService
{
    public function sponsorTransaction(
        string $userAddress,
        string $targetContract,
        string $callData,
        string $signature
    ): TransactionReceipt {
        // 1. Estimate gas cost
        $gasEstimate = $this->blockchain->estimateGas($callData);
        $gasCostInWei = $gasEstimate * $this->getGasPrice();

        // 2. Convert to stablecoin fee
        $usdcFee = $this->convertWeiToUsdc($gasCostInWei);

        // 3. Build UserOperation (ERC-4337)
        $userOp = new UserOperation([
            'sender' => $userAddress,
            'nonce' => $this->getNonce($userAddress),
            'callData' => $callData,
            'signature' => $signature,
            'paymasterAndData' => $this->paymaster->getPaymasterData($usdcFee),
        ]);

        // 4. Submit to bundler
        return $this->bundler->submitUserOperation($userOp);
    }
}
```

#### 3.6.3 Mobile Integration

```typescript
// User experience: Send USDC without ever needing ETH
async function sendStablecoin(to: string, amount: string, token: 'USDC' | 'USDT') {
  // 1. Build transaction (user signs, no gas needed)
  const callData = encodeTransfer(to, amount, token);
  const signature = await wallet.signUserOperation(callData);

  // 2. Submit to relayer (relayer pays gas)
  const response = await api.post('/api/v1/relayer/sponsor', {
    callData,
    signature,
  });

  // 3. User pays fee in stablecoins (deducted from transfer)
  // Example: Send 50 USDC, receive 49.95 USDC (0.05 USDC fee)
  return response.txHash;
}
```

#### 3.6.4 Fee Structure

| Network | Typical Gas Cost | USDC Fee | Notes |
|---------|-----------------|----------|-------|
| Polygon | ~$0.01-0.05 | 0.05 USDC | Low-cost default |
| Arbitrum | ~$0.10-0.30 | 0.30 USDC | L2 with Ethereum security |
| Ethereum | ~$1-10 | 5.00 USDC | Only for high-value txs |

---

### 3.7 Offline & Optimistic Exchange

> **Requirement**: Mobile networks are unstable; the app must handle disconnections gracefully.

#### 3.7.1 Optimistic UI Patterns

```typescript
// Exchange with slippage protection and offline handling
interface QuoteRequest {
  fromToken: string;
  toToken: string;
  amount: string;
}

interface Quote {
  id: string;
  fromAmount: string;
  toAmount: string;
  rate: string;
  validUntil: number;     // Unix timestamp (30 seconds from now)
  maxSlippage: string;    // e.g., "0.5%"
}

async function executeExchange(quote: Quote): Promise<ExchangeResult> {
  // 1. Validate quote hasn't expired
  if (Date.now() > quote.validUntil * 1000) {
    throw new QuoteExpiredError('Please request a new quote');
  }

  // 2. Optimistic UI update (show as pending immediately)
  updateLocalBalance({
    [quote.fromToken]: balance[quote.fromToken] - quote.fromAmount,
    [quote.toToken]: balance[quote.toToken] + quote.toAmount,
    pending: true,
  });

  // 3. Queue request if offline
  if (!navigator.onLine) {
    await queueOfflineTransaction({
      type: 'EXCHANGE',
      quote,
      queuedAt: Date.now(),
      expiresAt: quote.validUntil * 1000,
    });

    return { status: 'QUEUED_OFFLINE' };
  }

  // 4. Execute exchange
  try {
    const result = await api.post('/api/exchange/execute', { quoteId: quote.id });
    updateLocalBalance({ pending: false });
    return result;
  } catch (error) {
    // 5. Rollback optimistic update on failure
    rollbackLocalBalance();
    throw error;
  }
}
```

#### 3.7.2 Offline Queue Management

```typescript
// Handle reconnection and expired quotes
async function processOfflineQueue() {
  const queue = await getOfflineQueue();

  for (const item of queue) {
    // Auto-invalidate expired quotes
    if (Date.now() > item.expiresAt) {
      await removeFromQueue(item.id);
      notifyUser('Quote expired while offline. Please try again.');
      continue;
    }

    try {
      await executeQueuedTransaction(item);
      await removeFromQueue(item.id);
    } catch (error) {
      // Keep in queue for retry
      await updateQueueItem(item.id, { retryCount: item.retryCount + 1 });
    }
  }
}

// Listen for reconnection
NetInfo.addEventListener(state => {
  if (state.isConnected) {
    processOfflineQueue();
  }
});
```

---

## 4. Screen Specifications

### 4.1 Screen Map

```
┌─────────────────────────────────────────────────────────────────┐
│                        SCREEN MAP                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ONBOARDING                                                      │
│  ├── Welcome                                                     │
│  ├── Create/Import Wallet                                        │
│  ├── Passkey Setup                                               │
│  ├── Biometric Setup                                             │
│  └── Recovery Setup (optional)                                   │
│                                                                  │
│  MAIN (Tab Navigation)                                           │
│  ├── Home                                                        │
│  │   ├── Balance Overview                                        │
│  │   ├── Quick Actions                                           │
│  │   ├── Recent Transactions                                     │
│  │   └── TrustCert Status                                        │
│  │                                                               │
│  ├── Wallet                                                      │
│  │   ├── Asset List                                              │
│  │   ├── Asset Detail                                            │
│  │   ├── Receive (QR)                                            │
│  │   └── Privacy Balance                                         │
│  │                                                               │
│  ├── Pay                                                         │
│  │   ├── Scan QR                                                 │
│  │   ├── Payment Confirm                                         │
│  │   ├── Send                                                    │
│  │   └── Request                                                 │
│  │                                                               │
│  ├── Activity                                                    │
│  │   ├── Transaction List                                        │
│  │   ├── Transaction Detail                                      │
│  │   ├── Filters                                                 │
│  │   └── Export                                                  │
│  │                                                               │
│  └── Profile                                                     │
│      ├── Account Settings                                        │
│      ├── Security Settings                                       │
│      ├── TrustCert Management                                    │
│      ├── Privacy Settings                                        │
│      ├── Connected Devices                                       │
│      └── Support                                                 │
│                                                                  │
│  MODALS/SHEETS                                                   │
│  ├── Transaction Signing                                         │
│  ├── Biometric Prompt                                            │
│  ├── Privacy Shield Progress                                     │
│  ├── Certificate Verification                                    │
│  └── Error/Success States                                        │
│                                                                  │
│  FLOWS                                                           │
│  ├── TrustCert Application                                       │
│  ├── Privacy Shield/Unshield                                     │
│  ├── Merchant Payment                                            │
│  ├── P2P Transfer                                                │
│  └── Account Recovery                                            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Key Screen Wireframes

#### 4.2.1 Home Screen

```
┌─────────────────────────────────────────┐
│ ≡                        FinAegis    🔔 │
├─────────────────────────────────────────┤
│                                         │
│  Good morning, Alice                    │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │      Total Balance               │   │
│  │      $12,450.00                  │   │
│  │      ▲ +2.3% today               │   │
│  │                                  │   │
│  │  🔒 Shielded: $5,000.00          │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌────────┐ ┌────────┐ ┌────────┐      │
│  │  Send  │ │  Pay   │ │Receive │      │
│  │   ↑    │ │   📱   │ │   ↓    │      │
│  └────────┘ └────────┘ └────────┘      │
│                                         │
│  TrustCert Status                       │
│  ┌─────────────────────────────────┐   │
│  │ ✓ Business Trust    Exp: 2028   │   │
│  │ ○ White Hat         [Apply →]   │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Recent Activity                        │
│  ┌─────────────────────────────────┐   │
│  │ ↓ Coffee Shop      -$4.50  🔒   │   │
│  │ ↑ Salary Deposit   +$5,000     │   │
│  │ ↓ Amazon           -$125.00    │   │
│  │                   [View All →]  │   │
│  └─────────────────────────────────┘   │
│                                         │
├─────────────────────────────────────────┤
│  🏠     💰      📱      📊      👤     │
│ Home  Wallet   Pay   Activity Profile  │
└─────────────────────────────────────────┘
```

#### 4.2.2 Payment Screen

```
┌─────────────────────────────────────────┐
│ ←              Pay                    ✕ │
├─────────────────────────────────────────┤
│                                         │
│         ┌───────────────────┐          │
│         │                   │          │
│         │    📷 SCANNER     │          │
│         │                   │          │
│         │   Point camera    │          │
│         │   at QR code      │          │
│         │                   │          │
│         └───────────────────┘          │
│                                         │
│  ─────────── OR ───────────            │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  Enter address or username      │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Recent Payments                        │
│  ┌─────────────────────────────────┐   │
│  │ ☕ Coffee Shop                   │   │
│  │ 🏪 Local Grocery                 │   │
│  │ 👤 @alice                        │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │      🔒 Privacy Mode: ON         │   │
│  │      Transactions are shielded   │   │
│  └─────────────────────────────────┘   │
│                                         │
├─────────────────────────────────────────┤
│  🏠     💰      📱      📊      👤     │
└─────────────────────────────────────────┘
```

#### 4.2.3 TrustCert Application

```
┌─────────────────────────────────────────┐
│ ←        TrustCert Application        ✕ │
├─────────────────────────────────────────┤
│                                         │
│  Apply for: Business Trust Certificate  │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  Step 2 of 5: Business Details   │   │
│  │  ████████████░░░░░░░░  40%       │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Company Registration                   │
│  ┌─────────────────────────────────┐   │
│  │  Company Name                    │   │
│  │  ┌───────────────────────────┐  │   │
│  │  │ Acme Corporation          │  │   │
│  │  └───────────────────────────┘  │   │
│  │                                  │   │
│  │  Registration Number             │   │
│  │  ┌───────────────────────────┐  │   │
│  │  │ DE123456789               │  │   │
│  │  └───────────────────────────┘  │   │
│  │                                  │   │
│  │  Country of Incorporation        │   │
│  │  ┌───────────────────────────┐  │   │
│  │  │ Germany                 ▼ │  │   │
│  │  └───────────────────────────┘  │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Upload Documents                       │
│  ┌─────────────────────────────────┐   │
│  │  📄 Certificate of Inc.  [Upload]│   │
│  │  📄 UBO Declaration      [Upload]│   │
│  │  📄 Financial Statements [Upload]│   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │           Continue →             │   │
│  └─────────────────────────────────┘   │
│                                         │
└─────────────────────────────────────────┘
```

---

## 5. API Specifications

### 5.1 New Endpoints Required

#### 5.1.0 Card Issuance APIs (NEW - v2.5.0)

```yaml
# Provision Virtual Card
POST /api/v1/cards/provision
Request:
  deviceId: string
  walletType: 'APPLE_PAY' | 'GOOGLE_PAY'
  cardholderName: string
Response:
  cardId: string
  encryptedPassData: string      # Pass directly to native API
  activationData: string
  ephemeralPublicKey: string
  certificateChain: string[]

# Get User Cards
GET /api/v1/cards
Response:
  cards:
    - cardId: string
      last4: string
      network: 'VISA' | 'MASTERCARD'
      status: 'ACTIVE' | 'FROZEN' | 'CANCELLED'
      addedToWallet: boolean

# Freeze/Unfreeze Card
POST /api/v1/cards/{cardId}/freeze
DELETE /api/v1/cards/{cardId}/freeze

# JIT Funding Webhook (Card Issuer → Backend)
POST /api/webhooks/card-issuer/authorization
Request:
  authorization_id: string
  card_token: string
  amount: number
  currency: string
  merchant_name: string
  merchant_category: string
Response:
  approved: boolean
  hold_id: string
  decline_reason?: string
```

#### 5.1.0b Gas Relayer APIs (NEW - v2.5.0)

```yaml
# Sponsor Transaction (Meta-Transaction)
POST /api/v1/relayer/sponsor
Request:
  userAddress: string
  callData: string              # Encoded transaction calldata (0x...)
  signature: string             # User's signature (0x...)
  network: 'polygon' | 'arbitrum' | 'optimism' | 'base' | 'ethereum'
  feeToken: 'USDC' | 'USDT'
Response:
  txHash: string
  userOpHash: string
  gasUsed: number
  feeCharged: string            # Fee in USDC/USDT
  feeCurrency: string

# Estimate Gas Fee
POST /api/v1/relayer/estimate
Request:
  callData: string
  network: string
Response:
  estimatedGas: number
  feeUsdc: string
  feeUsdt: string
  network: string

# Get Supported Networks
GET /api/v1/relayer/networks
Response:
  networks:
    - chainId: number           # e.g., 137 for Polygon
      name: string              # e.g., "polygon"
      feeToken: string          # e.g., "USDC"
      averageFee: string        # e.g., "0.0200"
```

#### 5.1.0c TrustCert Presentation APIs (NEW - v2.5.0)

```yaml
# Generate Verifiable Presentation (QR Code / Deep Link)
POST /api/v1/trustcert/{certificateId}/present
Request:
  requestedClaims: string[]     # Optional: ['certificate_type', 'valid_until']
  validityMinutes: number       # Optional: 1-60, default 15
Response:
  presentationToken: string     # 64-char secure token
  qrCodeData: string            # JSON for QR code generation
  deepLink: string              # finaegis://trustcert/verify/{token}
  verificationUrl: string       # HTTPS URL for web verification
  expiresAt: datetime
  claims: object                # Selected claims included

# Verify Presentation Token (PUBLIC - No Auth Required)
GET /api/v1/trustcert/verify/{token}
Response:
  valid: boolean
  certificateType: string       # e.g., "BUSINESS_TRUST"
  trustLevel: string            # e.g., "verified"
  claims: object                # Disclosed claims
  issuer: string                # e.g., "did:web:finaegis.org"
  expiresAt: datetime
  error: string                 # Only if valid=false
```

**Mobile Usage Example:**

```typescript
// Generate QR code for in-person verification
async function shareCredential(certificateId: string) {
  const response = await api.post(`/api/v1/trustcert/${certificateId}/present`, {
    requestedClaims: ['certificate_type', 'valid_until'],
    validityMinutes: 15,
  });

  // Show QR code to verifier
  return generateQRCode(response.qrCodeData);
}

// Verifier scans QR code and calls public endpoint
async function verifyCredential(token: string) {
  const result = await api.get(`/api/v1/trustcert/verify/${token}`);
  return result.valid;
}
```

#### 5.1.1 Stablecoin Commerce APIs

```yaml
# Payment Request
POST /api/commerce/payment-requests
Request:
  merchantId: string
  amount: string
  currency: string
  acceptedTokens: string[]
  orderId: string
  expiresIn: number (seconds)
Response:
  requestId: string
  qrCodeData: string
  deepLink: string
  expiresAt: datetime

# Execute Payment
POST /api/commerce/payments
Request:
  requestId: string
  fromAddress: string
  token: string
  signature: string
  privacyMode: boolean
Response:
  paymentId: string
  status: 'pending' | 'confirmed' | 'failed'
  txHash: string
  shieldedTxId?: string

# Get Payment Status
GET /api/commerce/payments/{paymentId}
Response:
  paymentId: string
  status: string
  confirmations: number
  merchantConfirmed: boolean
```

#### 5.1.2 Privacy Layer APIs

```yaml
# Shield Funds (Deposit to Privacy Pool)
POST /api/privacy/shield
Request:
  token: string
  amount: string
  proof: string (ZK proof of ownership)
Response:
  shieldId: string
  status: 'pending' | 'shielded'
  auditLogId: string

# Unshield Funds (Withdraw from Privacy Pool)
POST /api/privacy/unshield
Request:
  token: string
  amount: string
  recipient: string
  proof: string (ZK proof)
Response:
  unshieldId: string
  txHash: string
  status: string

# Private Transfer
POST /api/privacy/transfer
Request:
  token: string
  amount: string
  recipientViewingKey: string
  proof: string
Response:
  transferId: string
  status: string

# Get Shielded Balance
GET /api/privacy/balance
Response:
  balances:
    - token: string
      shieldedAmount: string
      pendingShield: string
      pendingUnshield: string

# Generate Proof of Innocence
POST /api/privacy/proof-of-innocence
Request:
  proofType: 'SANCTIONS' | 'ORIGIN' | 'COMPLIANCE'
  sanctionsListVersion: string
Response:
  proof: string
  publicInputs: object
  expiresAt: datetime
  verificationUrl: string
```

#### 5.1.3 TrustCert APIs

```yaml
# Start Certificate Application
POST /api/trustcert/applications
Request:
  certType: string
  applicantType: 'INDIVIDUAL' | 'BUSINESS'
Response:
  applicationId: string
  requiredDocuments: string[]
  estimatedDays: number
  fee: string

# Upload Document
POST /api/trustcert/applications/{id}/documents
Request:
  documentType: string
  file: binary
Response:
  documentId: string
  verificationStatus: 'pending' | 'verified' | 'rejected'

# Get Application Status
GET /api/trustcert/applications/{id}
Response:
  applicationId: string
  status: string
  currentStep: number
  totalSteps: number
  documents: array
  estimatedCompletion: datetime

# Get User Certificates
GET /api/trustcert/certificates
Response:
  certificates:
    - tokenId: string
      certType: string
      issuedAt: datetime
      expiresAt: datetime
      status: string
      onChainUrl: string

# Generate Verification Proof
POST /api/trustcert/certificates/{tokenId}/verify
Request:
  proofRequest: object (what to prove)
Response:
  proof: string
  publicInputs: object
  verifiablePresentation: string

# Revoke Certificate
DELETE /api/trustcert/certificates/{tokenId}
Request:
  reason: string
Response:
  revoked: boolean
  txHash: string
```

### 5.2 WebSocket Channels (New)

```yaml
# Privacy Pool Events
Channel: private-privacy.{userId}
Events:
  - shield.confirmed
  - unshield.confirmed
  - transfer.received
  - proof.generated

# Commerce Events
Channel: private-commerce.{merchantId}
Events:
  - payment.received
  - payment.confirmed
  - settlement.completed

# TrustCert Events
Channel: private-trustcert.{userId}
Events:
  - application.updated
  - document.verified
  - certificate.issued
  - certificate.expiring
```

---

## 6. Backend Implementation Status

### 6.1 Domain Status (v2.4.0 Complete)

The following domains have been implemented and are ready for mobile integration:

```
app/Domain/
├── KeyManagement/              # ✅ IMPLEMENTED (v2.4.0 Phase 1)
│   ├── Enums/
│   │   ├── ShardType.php       # device, auth, recovery
│   │   └── ShardStatus.php     # active, revoked, used
│   ├── ValueObjects/
│   │   ├── KeyShard.php        # Immutable shard representation
│   │   └── ReconstructedKey.php
│   ├── Services/
│   │   ├── ShamirService.php   # 2-of-3 threshold splitting
│   │   ├── EncryptionService.php
│   │   ├── KeyReconstructionService.php
│   │   └── ShardDistributionService.php
│   ├── HSM/
│   │   ├── DemoHsmProvider.php
│   │   └── HsmIntegrationService.php
│   └── Events/                 # Event-sourced key lifecycle
│
├── Privacy/                    # ✅ IMPLEMENTED (v2.4.0 Phase 2)
│   ├── Enums/
│   │   ├── ProofType.php       # ownership, balance, identity, etc.
│   │   └── PrivacyLevel.php    # transparent, shielded, selective
│   ├── ValueObjects/
│   │   ├── ZkProof.php
│   │   └── SelectiveDisclosure.php
│   ├── Services/
│   │   ├── ZkKycService.php    # Verify KYC without exposing PII
│   │   ├── SelectiveDisclosureService.php
│   │   ├── DemoZkProver.php
│   │   └── ProofOfInnocenceService.php  # RAILGUN-style compliance
│   └── Events/                 # ZkKycVerified, ProofOfInnocenceGenerated
│
├── Commerce/                   # ✅ IMPLEMENTED (v2.4.0 Phase 3)
│   ├── Enums/
│   │   ├── TokenType.php       # identity, access, achievement, etc.
│   │   ├── MerchantStatus.php  # State machine: pending → verified → active
│   │   ├── AttestationType.php
│   │   └── CredentialType.php
│   ├── ValueObjects/
│   │   ├── SoulboundToken.php
│   │   ├── PaymentAttestation.php
│   │   └── VerifiableCredential.php
│   ├── Services/
│   │   ├── SoulboundTokenService.php
│   │   ├── MerchantOnboardingService.php
│   │   ├── PaymentAttestationService.php
│   │   └── CredentialIssuanceService.php
│   ├── Contracts/
│   │   ├── TokenIssuerInterface.php
│   │   └── AttestationServiceInterface.php
│   └── Events/                 # SoulboundTokenIssued, MerchantOnboarded
│
├── TrustCert/                  # ✅ IMPLEMENTED (v2.4.0 Phase 4)
│   ├── Enums/
│   │   ├── CertificateStatus.php    # pending, active, suspended, revoked, expired
│   │   ├── TrustLevel.php           # unknown, basic, verified, high, ultimate
│   │   ├── RevocationReason.php     # RFC 5280 compliant
│   │   └── IssuerType.php           # root_ca, intermediate_ca, trusted_issuer
│   ├── ValueObjects/
│   │   ├── Certificate.php          # Digital certificate with fingerprint
│   │   ├── RevocationEntry.php      # StatusList2021 compatible
│   │   ├── TrustedIssuer.php
│   │   └── TrustChain.php           # Chain of trust validation
│   ├── Services/
│   │   ├── CertificateAuthorityService.php  # Internal CA
│   │   ├── VerifiableCredentialService.php  # W3C VC standard
│   │   ├── RevocationRegistryService.php    # Revocation tracking
│   │   └── TrustFrameworkService.php        # Multi-issuer trust
│   ├── Contracts/
│   │   ├── CertificateAuthorityInterface.php
│   │   ├── RevocationRegistryInterface.php
│   │   └── TrustFrameworkInterface.php
│   └── Events/                      # CertificateIssued, CredentialRevoked
│
├── Mobile/                     # ✅ IMPLEMENTED (v2.2.0)
│   ├── Services/
│   │   ├── MobileDeviceService.php
│   │   ├── BiometricAuthenticationService.php
│   │   ├── PushNotificationService.php
│   │   └── MobileSessionService.php
│   └── Events/                 # DeviceRegistered, BiometricVerified
│
├── CardIssuance/               # ✅ IMPLEMENTED (v2.5.0)
│   ├── Enums/
│   │   ├── CardStatus.php                  # pending, active, frozen, cancelled, expired
│   │   ├── WalletType.php                  # apple_pay, google_pay
│   │   └── AuthorizationDecision.php       # JIT funding decisions
│   ├── ValueObjects/
│   │   ├── VirtualCard.php
│   │   ├── ProvisioningData.php
│   │   └── AuthorizationRequest.php
│   ├── Services/
│   │   ├── CardProvisioningService.php     # Apple/Google Pay push provisioning
│   │   └── JitFundingService.php           # Real-time JIT authorization (<2s)
│   ├── Adapters/
│   │   └── DemoCardIssuerAdapter.php       # Demo implementation
│   ├── Contracts/
│   │   └── CardIssuerInterface.php         # Marqeta/Lithic/Stripe adapter
│   ├── Events/
│   │   ├── CardProvisioned.php
│   │   ├── AuthorizationApproved.php
│   │   └── AuthorizationDeclined.php
│   └── config/cardissuance.php             # Configuration
│
├── Relayer/                    # ✅ IMPLEMENTED (v2.5.0)
│   ├── Enums/
│   │   └── SupportedNetwork.php            # polygon, arbitrum, optimism, base, ethereum
│   ├── ValueObjects/
│   │   └── UserOperation.php               # ERC-4337 UserOperation
│   ├── Services/
│   │   ├── GasStationService.php           # Main meta-transaction service
│   │   ├── DemoPaymasterService.php        # Demo paymaster
│   │   └── DemoBundlerService.php          # Demo bundler
│   ├── Contracts/
│   │   ├── PaymasterInterface.php          # ERC-4337 paymaster interface
│   │   └── BundlerInterface.php            # UserOperation bundling interface
│   ├── Events/
│   │   └── TransactionSponsored.php
│   └── config/relayer.php                  # Network configs, bundler settings
│
└── TrustCert/                  # ✅ ENHANCED (v2.5.0)
    ├── Services/
    │   └── PresentationService.php         # QR/Deep Link presentation generation
    └── Http/Controllers/Api/
        └── PresentationController.php      # Presentation & verification endpoints
```

### 6.2 Configuration Files

| File | Purpose | Status |
|------|---------|--------|
| `config/keymanagement.php` | HSM, sharding, encryption settings | ✅ |
| `config/privacy.php` | ZK circuits, selective disclosure, POI | ✅ |
| `config/commerce.php` | SBT, merchant tiers, attestation | ✅ |
| `config/trustcert.php` | CA, credentials, revocation, trust framework | ✅ |
| `config/mobile.php` | Device limits, biometrics, push providers | ✅ |
| `config/cardissuance.php` | Card issuer adapters, JIT funding, limits | ✅ |
| `config/relayer.php` | Network configs, bundler, paymaster settings | ✅ |

### 6.3 Database Migrations (Mobile App Specific)

The following migrations are needed for mobile app features beyond the existing backend domains:

```php
// 2026_02_XX_000001_create_privacy_tables.php
Schema::create('shielded_balances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('token_address');
    $table->string('commitment');           // Pedersen commitment
    $table->decimal('amount', 36, 18);
    $table->string('nullifier_hash')->unique();
    $table->timestamps();
});

Schema::create('shield_transactions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->enum('type', ['SHIELD', 'UNSHIELD', 'TRANSFER']);
    $table->string('token_address');
    $table->decimal('amount', 36, 18);
    $table->string('tx_hash')->nullable();
    $table->string('proof');
    $table->json('public_inputs');
    $table->enum('status', ['pending', 'confirmed', 'failed']);
    $table->timestamps();
});

Schema::create('audit_vault_entries', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('transaction_id');
    $table->text('encrypted_data');         // AES-256-GCM
    $table->string('encryption_key_id');    // Shamir shard reference
    $table->json('key_holders');            // Required signers
    $table->boolean('is_decrypted')->default(false);
    $table->timestamp('decrypted_at')->nullable();
    $table->string('decryption_reason')->nullable();
    $table->timestamps();
});

// 2026_02_XX_000002_create_commerce_tables.php
Schema::create('merchants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('business_name');
    $table->string('merchant_code')->unique();
    $table->json('accepted_tokens');
    $table->string('settlement_address');
    $table->enum('settlement_frequency', ['instant', 'daily', 'weekly']);
    $table->decimal('fee_rate', 5, 4);
    $table->boolean('is_verified')->default(false);
    $table->timestamps();
});

Schema::create('stablecoin_payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('merchant_id');
    $table->uuid('payer_id')->nullable();
    $table->string('order_id');
    $table->string('token_address');
    $table->decimal('amount', 36, 18);
    $table->decimal('fiat_amount', 18, 2);
    $table->string('fiat_currency', 3);
    $table->decimal('exchange_rate', 18, 8);
    $table->string('tx_hash')->nullable();
    $table->boolean('is_shielded')->default(false);
    $table->enum('status', ['pending', 'paid', 'confirmed', 'settled', 'refunded']);
    $table->timestamps();
});

// 2026_02_XX_000003_create_trustcert_tables.php
Schema::create('certificates', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('token_id')->unique();
    $table->string('wallet_address');
    $table->enum('cert_type', [
        'PERSONAL_TRUST',
        'BUSINESS_TRUST',
        'DUAL_USE_EXPORT',
        'ACCREDITED_INVESTOR',
        'WHITE_HAT'
    ]);
    $table->string('credential_hash');
    $table->text('encrypted_data');
    $table->enum('status', ['pending', 'active', 'suspended', 'revoked', 'expired']);
    $table->string('blockchain', 50);
    $table->string('contract_address');
    $table->string('mint_tx_hash')->nullable();
    $table->timestamp('issued_at')->nullable();
    $table->timestamp('expires_at');
    $table->timestamp('revoked_at')->nullable();
    $table->string('revocation_reason')->nullable();
    $table->timestamps();
});

Schema::create('certificate_applications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->enum('cert_type', [...]);
    $table->enum('applicant_type', ['INDIVIDUAL', 'BUSINESS']);
    $table->json('applicant_data');
    $table->enum('status', [
        'draft', 'submitted', 'under_review',
        'additional_info_required', 'approved',
        'rejected', 'issued'
    ]);
    $table->integer('current_step');
    $table->decimal('fee_amount', 18, 2);
    $table->boolean('fee_paid')->default(false);
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->uuid('reviewer_id')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
});
```

### 6.3 Smart Contract Deployments

| Contract | Network | Purpose |
|----------|---------|---------|
| TrustCertSBT | Polygon | Soulbound Token for certificates |
| ShieldPool | Polygon | Privacy pool (RAILGUN fork) |
| PaymentRouter | Polygon | Stablecoin payment routing |
| ProofVerifier | Polygon | ZK proof verification |

### 6.4 External Integrations

| Integration | Purpose | Priority |
|-------------|---------|----------|
| RAILGUN SDK | Privacy pool integration | High |
| Polygon ID | zkKYC verification | High |
| Chainlink CCIP | Cross-chain messaging | Medium |
| The Graph | Blockchain indexing | Medium |
| Arweave | Decentralized credential storage | Medium |

---

## 7. Implementation Roadmap

### Phase 1: Foundation (Weeks 1-4)

| Task | Description | Owner |
|------|-------------|-------|
| Key Sharding | Implement Shamir's Secret Sharing | Backend |
| HSM Integration | Connect to cloud HSM for auth shards | Backend |
| Mobile Scaffold | Expo project with core navigation | Mobile |
| Auth Flow | Passkey + Biometric implementation | Mobile |

### Phase 2: Commerce (Weeks 5-8)

| Task | Description | Owner |
|------|-------------|-------|
| Merchant Onboarding | Registration and verification flow | Backend |
| Payment Protocol | QR generation and payment execution | Backend |
| Merchant SDK | TypeScript SDK for integration | Backend |
| Pay Screen | Scanner and payment confirmation | Mobile |
| Settlement Engine | Batch settlement processing | Backend |

### Phase 3: Privacy Layer (Weeks 9-14)

| Task | Description | Owner |
|------|-------------|-------|
| Shield Pool Contract | Deploy and test on testnet | Blockchain |
| ZK Prover Integration | snarkjs WASM in mobile app | Mobile |
| Audit Vault | Encrypted logging with key sharding | Backend |
| Privacy UI | Shield/unshield flows in app | Mobile |
| Proof of Innocence | Sanctions proof generation | Backend |

### Phase 4: TrustCert (Weeks 15-20)

| Task | Description | Owner |
|------|-------------|-------|
| SBT Contract | TrustCertSBT deployment | Blockchain |
| Application Flow | Multi-step application process | Backend + Mobile |
| Verification Pipeline | Document + background checks | Backend |
| ZK Verification | Proof generation for certificates | Backend |
| Certificate UI | Management and verification screens | Mobile |

### Phase 5: Polish & Launch (Weeks 21-24)

| Task | Description | Owner |
|------|-------------|-------|
| Security Audit | Third-party audit (Trail of Bits) | Security |
| Beta Testing | TestFlight + Play Console beta | QA |
| Documentation | API docs, user guides | Docs |
| App Store Prep | Screenshots, descriptions, review | Marketing |
| Mainnet Launch | Production deployment | DevOps |

---

## 8. Security Considerations

### 8.1 Threat Model

| Threat | Mitigation |
|--------|------------|
| Key Compromise | Shamir sharding (2-of-3), no single point of failure |
| Replay Attacks | Nonce-based signing, session binding |
| Privacy Leakage | ZK proofs, encrypted audit logs |
| Regulatory Seizure | Multi-party decryption (3-of-5 key holders) |
| Smart Contract Exploit | Formal verification, timelocks, upgradability |

### 8.2 Compliance Requirements

| Regulation | Requirement | Implementation |
|------------|-------------|----------------|
| GDPR | Data minimization | zkKYC, encrypted storage |
| MiCA | Transaction monitoring | Audit vault, pattern detection |
| Travel Rule | Beneficiary identification | Selective disclosure proofs |
| AML/CFT | Sanctions screening | Proof of Innocence |

---

## 9. Success Metrics

| Metric | Target (6 months) |
|--------|-------------------|
| Mobile App Downloads | 50,000 |
| Monthly Active Users | 20,000 |
| Transaction Volume | $10M |
| TrustCerts Issued | 500 |
| Merchant Partners | 100 |
| Privacy Pool TVL | $5M |

---

## 10. Open Questions

1. **Privacy Pool Jurisdiction**: Which legal entity operates the shield pool?
2. **TrustCert Pricing**: Fee structure for different certificate types?
3. **Merchant Fees**: Revenue split between FinAegis and merchants?
4. **Multi-Chain Strategy**: Deploy on which L2s first (Polygon, Base, Arbitrum)?
5. **Hardware Wallet Integration**: Support Ledger/Trezor for privacy transactions?

---

## Appendix A: API Error Code Catalog

### Error Response Format

All API errors follow a consistent format:

```json
{
  "success": false,
  "error": {
    "code": "ERR_AUTH_001",
    "message": "Authentication token has expired",
    "details": {
      "expired_at": "2026-02-01T10:30:00Z"
    }
  },
  "request_id": "req_abc123"
}
```

### Error Code Ranges

| Range | Category | Description |
|-------|----------|-------------|
| `ERR_AUTH_0XX` | Authentication | Login, tokens, sessions |
| `ERR_DEVICE_1XX` | Device Management | Registration, biometrics |
| `ERR_WALLET_2XX` | Wallet Operations | Balances, transactions |
| `ERR_PRIVACY_3XX` | Privacy Layer | Shield/unshield, ZK proofs |
| `ERR_COMMERCE_4XX` | Commerce | Payments, merchants |
| `ERR_CERT_5XX` | TrustCert | Certificates, verification |
| `ERR_SYSTEM_9XX` | System | Rate limiting, maintenance |

### Authentication Errors (ERR_AUTH_0XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_AUTH_001` | 401 | Token expired | Refresh token or re-authenticate |
| `ERR_AUTH_002` | 401 | Invalid token | Re-authenticate |
| `ERR_AUTH_003` | 401 | Session not found | Re-authenticate |
| `ERR_AUTH_004` | 403 | Biometric required | Prompt biometric |
| `ERR_AUTH_005` | 403 | Device not registered | Register device first |
| `ERR_AUTH_006` | 429 | Too many login attempts | Wait and retry |
| `ERR_AUTH_007` | 403 | Account suspended | Contact support |

### Device Errors (ERR_DEVICE_1XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_DEVICE_101` | 400 | Device already registered | Use existing device |
| `ERR_DEVICE_102` | 400 | Device limit reached | Remove old device |
| `ERR_DEVICE_103` | 400 | Biometric not available | Use password fallback |
| `ERR_DEVICE_104` | 400 | Invalid biometric signature | Retry biometric |
| `ERR_DEVICE_105` | 400 | Push token invalid | Re-register push |

### Wallet Errors (ERR_WALLET_2XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_WALLET_201` | 400 | Insufficient balance | Reduce amount or add funds |
| `ERR_WALLET_202` | 400 | Invalid address | Verify recipient address |
| `ERR_WALLET_203` | 400 | Transaction pending | Wait for confirmation |
| `ERR_WALLET_204` | 400 | Gas estimation failed | Retry with higher gas |
| `ERR_WALLET_205` | 400 | Nonce conflict | Retry transaction |
| `ERR_WALLET_206` | 503 | Network congested | Retry later |

### Privacy Errors (ERR_PRIVACY_3XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_PRIVACY_301` | 400 | Invalid ZK proof | Regenerate proof |
| `ERR_PRIVACY_302` | 400 | Insufficient shielded balance | Shield more funds |
| `ERR_PRIVACY_303` | 400 | Nullifier already used | Transaction already processed |
| `ERR_PRIVACY_304` | 400 | Proof generation timeout | Retry with smaller amount |
| `ERR_PRIVACY_305` | 400 | Compliance proof required | Generate proof of innocence |

### Commerce Errors (ERR_COMMERCE_4XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_COMMERCE_401` | 400 | Payment request expired | Request new QR code |
| `ERR_COMMERCE_402` | 400 | Merchant not verified | Contact merchant |
| `ERR_COMMERCE_403` | 400 | Token not accepted | Use different token |
| `ERR_COMMERCE_404` | 400 | Payment already completed | No action needed |
| `ERR_COMMERCE_405` | 400 | Settlement pending | Wait for settlement |

### TrustCert Errors (ERR_CERT_5XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_CERT_501` | 400 | Certificate expired | Renew certificate |
| `ERR_CERT_502` | 400 | Certificate revoked | Apply for new certificate |
| `ERR_CERT_503` | 400 | Verification failed | Re-submit documents |
| `ERR_CERT_504` | 400 | Application incomplete | Complete all steps |
| `ERR_CERT_505` | 400 | Issuer not trusted | Use verified issuer |

### System Errors (ERR_SYSTEM_9XX)

| Code | HTTP | Message | Recovery Action |
|------|------|---------|-----------------|
| `ERR_SYSTEM_901` | 429 | Rate limit exceeded | Wait and retry |
| `ERR_SYSTEM_902` | 503 | Service maintenance | Retry later |
| `ERR_SYSTEM_903` | 500 | Internal error | Report to support |
| `ERR_SYSTEM_904` | 502 | Upstream service error | Retry later |

---

## Appendix B: API Versioning Strategy

### Version Format

APIs use URL-based versioning with semantic versioning principles:

```
https://api.finaegis.com/v{major}/endpoint
```

### Current Versions

| Version | Status | Deprecation Date |
|---------|--------|------------------|
| `v1` | Active | - |
| `v2` | Planned (Breaking changes) | - |

### Versioning Rules

1. **Major version** (`v1`, `v2`): Breaking changes that require client updates
2. **Minor versions**: Backward-compatible additions (no URL change)
3. **Patch versions**: Bug fixes (no URL change)

### Breaking Changes (Require New Major Version)

- Removing an endpoint
- Removing a required field from response
- Changing field types
- Changing authentication method
- Changing error code format

### Non-Breaking Changes (No Version Bump)

- Adding new endpoints
- Adding optional request parameters
- Adding new fields to responses
- Adding new error codes
- Performance improvements

### Version Header

Clients should include API version in headers for tracking:

```http
X-API-Version: 2024-02-01
Accept: application/json
```

### Deprecation Policy

1. **Announcement**: 6 months before deprecation
2. **Warning Headers**: `Deprecation` and `Sunset` headers added
3. **Documentation**: Migration guide published
4. **Grace Period**: 3 months after sunset date
5. **Removal**: Endpoint returns 410 Gone

Example deprecation headers:
```http
Deprecation: true
Sunset: Sat, 01 Aug 2026 00:00:00 GMT
Link: <https://docs.finaegis.com/migration/v1-to-v2>; rel="deprecation"
```

### Mobile SDK Versioning

| SDK Version | Min API | Max API | Notes |
|-------------|---------|---------|-------|
| 1.0.x | v1 | v1 | Initial release |
| 1.1.x | v1 | v1 | Feature additions |
| 2.0.x | v1 | v2 | Supports both versions |

---

## Appendix C: Glossary

| Term | Definition |
|------|------------|
| **Shamir's Secret Sharing** | Cryptographic algorithm to split secrets into shards |
| **ZK-SNARK** | Zero-Knowledge Succinct Non-Interactive Argument of Knowledge |
| **Soulbound Token (SBT)** | Non-transferable NFT for credentials |
| **Proof of Innocence** | Cryptographic proof that funds are not from sanctioned sources |
| **Shield Pool** | Privacy pool where funds are mixed using ZK proofs |
| **TEE** | Trusted Execution Environment (secure hardware enclave) |
| **HSM** | Hardware Security Module for key storage |
| **W3C VC** | W3C Verifiable Credentials standard for digital attestations |
| **StatusList2021** | W3C standard for credential revocation lists |
| **RFC 5280** | Internet X.509 PKI Certificate and CRL Profile |

---

*Document Version: 1.4*
*Last Updated: February 2, 2026*
*Author: FinAegis Architecture Team*
*Backend Status: v2.5.1 APIs Complete (Card Issuance, Gas Relayer, TrustCert Presentation, Passkey Support)*

### Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.4 | 2026-02-02 | Added Passkey/WebAuthn spec (3.2.1), Privacy Protocol decision (RAILGUN-inspired) |
| 1.3 | 2026-02-01 | Added Card Issuance (3.4), Gas Relayer API (5.1.0b), TrustCert Presentation API (5.1.0c) |
| 1.2 | 2026-01-31 | Initial privacy layer and TrustCert documentation |

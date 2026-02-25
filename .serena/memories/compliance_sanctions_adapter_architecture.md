# Compliance/Sanctions Adapter Architecture - Complete Analysis

## Executive Summary
The FinAegis codebase has a comprehensive compliance and sanctions checking system built on event sourcing and CQRS patterns. The architecture is flexible for adding new external adapters (like Chainalysis) following established patterns used by RegTech (regulatory filing) adapters and service connectors.

---

## 1. Core Compliance Domain Structure

### Domain Location
`app/Domain/Compliance/` - Master compliance domain with:
- **Aggregates**: `AmlScreeningAggregate` - Event-sourced root for screening lifecycle
- **Models**: `AmlScreening`, `AmlScreeningEvent`, `CustomerRiskProfile`, `ComplianceAlert`, etc.
- **Services**: `AmlScreeningService`, `ComplianceService`, `CustomerRiskService`, etc.
- **Events**: 40+ domain events (AmlScreeningStarted, AmlScreeningCompleted, ScreeningMatchFound, etc.)
- **Repositories**: `AmlScreeningRepository`, `ComplianceAlertRepository`, etc.
- **Projectors**: `AmlScreeningProjector` - Read model projections
- **Routes**: `app/Domain/Compliance/Routes/api.php` - REST endpoints

### Key Domain Interfaces
**Location**: `app/Domain/Shared/Contracts/ComplianceCheckInterface`
```php
interface ComplianceCheckInterface {
    public function getKYCStatus(string $userId): array;
    public function hasMinimumKYCLevel(string $userId, string $requiredLevel): bool;
    public function validateTransaction(array $transaction): array;
    public function checkTransactionLimits(string $userId, string $amount, string $currency): array;
    public function screenAML(string $userId): array;  // PRIMARY ENTRY POINT
    public function isUserBlocked(string $userId): array;
    public function reportSuspiciousActivity(array $report): string;
}
```

---

## 2. Existing AML Screening Architecture

### AmlScreeningService (lines 1-709)
**Location**: `app/Domain/Compliance/Services/AmlScreeningService.php`

#### Current Sanctions List Configuration (Hard-coded)
```php
private array $sanctionsLists = [
    'OFAC' => 'https://api.ofac.treasury.gov/v1/',
    'EU'   => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content',
    'UN'   => 'https://api.un.org/sc/suborg/en/sanctions/un-sc-consolidated-list',
];
private string $provider = 'Internal';
```

#### Internal Mock Lists (Demo Mode)
The service includes built-in simulation for demo/test purposes:
- **OFAC Simulation** (`checkOFACList()`): Returns mock matches if name contains "test" or "sanctioned"
- **PEP Database Simulation** (`checkPEPDatabase()`): Simulates PEP check with keyword matching (minister, senator, governor, official)
- **Adverse Media Simulation** (`searchAdverseMedia()`): Returns articles if name contains "fraud" or "scandal"

#### Screening Methods
1. `performComprehensiveScreening($entity, $parameters)`: Runs all 3 screening types
2. `performSanctionsCheck($searchParams)`: OFAC, EU, UN lists
3. `performPEPCheck($searchParams)`: Politically Exposed Persons
4. `performAdverseMediaCheck($searchParams)`: News/media screening
5. `performScreeningByType($entity, $type, $parameters)`: Single type only

#### How Sanctions Checks Are Triggered
- **GraphQL Mutation**: `TriggerAmlCheckMutation` (app/GraphQL/Mutations/Compliance/TriggerAmlCheckMutation.php)
  - Accepts: `entity_id`, `entity_type`, `check_type`
  - Returns: `ComplianceAlert`
  - Internally calls: `$amlScreeningService->performComprehensiveScreening()`

- **REST API**: Via `compliance/alerts` endpoints with POST (defined in Routes/api.php)

- **Service Injection**: Direct service calls in other domains (Exchange, Lending, AgentProtocol, etc.)

#### Risk Scoring Logic
```php
calculateOverallRisk($sanctions, $pep, $adverseMedia): string {
    // CRITICAL if any sanctions match found
    // HIGH if PEP match or serious adverse media allegations
    // MEDIUM if any adverse media found
    // LOW if all clear
}
```

---

## 3. AML Screening Model Structure

**Location**: `app/Domain/Compliance/Models/AmlScreening.php`

### Screening Types
```php
const TYPE_SANCTIONS = 'sanctions';
const TYPE_PEP = 'pep';
const TYPE_ADVERSE_MEDIA = 'adverse_media';
const TYPE_COMPREHENSIVE = 'comprehensive';
```

### Risk Levels
```php
const RISK_LOW = 'low';
const RISK_MEDIUM = 'medium';
const RISK_HIGH = 'high';
const RISK_CRITICAL = 'critical';
```

### Key Fields
- `screening_number`: Unique identifier (AML-2026-00001)
- `type`: One of 4 types above
- `status`: completed, failed, pending
- `provider`: Current provider (default: "Internal")
- `search_parameters`: array of name, DOB, country, ID, etc.
- `sanctions_results`: array with matches/lists_checked/total_matches
- `pep_results`: array with is_pep/position/country/matches
- `adverse_media_results`: array with articles/severity/categories
- `total_matches`: Aggregated count
- `overall_risk`: Enum value (low, medium, high, critical)
- `lists_checked`: Array of regulatory lists screened
- `reviewed_by`, `review_decision`, `review_notes`: Compliance officer review

---

## 4. Event Sourcing Pattern (AmlScreeningAggregate)

**Location**: `app/Domain/Compliance/Aggregates/AmlScreeningAggregate.php`

### Event Lifecycle
1. `AmlScreeningStarted` - Screening initiated with parameters
2. `AmlScreeningResultsRecorded` - Results captured from provider
3. `AmlScreeningReviewed` - Compliance officer review
4. `AmlScreeningMatchStatusUpdated` - Individual match disposition changes
5. `AmlScreeningCompleted` - Screening finalized

### Aggregate Methods
- `startScreening()`: Initialize aggregate with search params
- `recordResults()`: Store sanctions/PEP/adverse media results
- `reviewScreening()`: Log compliance officer decision
- `updateMatchStatus()`: Change individual match disposition (confirmed, dismissed, false_positive)

---

## 5. Existing RegTech Adapter Pattern (Template for Chainalysis)

### Interface-Based Design
**Location**: `app/Domain/RegTech/Contracts/RegulatoryFilingAdapterInterface`

```php
interface RegulatoryFilingAdapterInterface {
    public function getName(): string;
    public function getJurisdiction(): Jurisdiction;
    public function getSupportedReportTypes(): array;
    public function submitReport(string $type, array $data, array $metadata = []): array;
    public function checkStatus(string $reference): array;
    public function validateReport(string $type, array $data): array;
    public function getApiEndpoint(): string;
    public function isAvailable(): bool;
    public function isSandboxMode(): bool;
}
```

### Base Adapter Pattern
**Location**: `app/Domain/RegTech/Adapters/AbstractRegulatoryAdapter.php`

Provides:
- Common configuration loading from Laravel config
- Demo/sandbox mode support via `regtech.demo_mode` config
- API endpoint URL building
- Validation helpers (validateCommonFields, etc.)
- Status checking simulation

### Concrete Adapter Examples
- `FinCENAdapter` (US) - Handles CTR, SAR, CMIR, FBAR reports
- `FCAAdapter` (UK)
- `ESMAAdapter` (EU)
- `MASAdapter` (Singapore)

**Pattern**: Each adapter extends AbstractRegulatoryAdapter and overrides:
- `getRegulatorKey()`: Returns lowercase regulator name (e.g., 'fincen')
- `getName()`: Display name
- `getJurisdiction()`: Returns Jurisdiction enum
- `getSupportedReportTypes()`: Array of report types
- `validateReport()`: Type-specific validation logic

---

## 6. Service Connector Pattern (For External APIs)

### Custodian Connector Base
**Location**: `app/Domain/Custodian/Connectors/BaseCustodianConnector.php`

Used for integrating external payment/banking services:
- Implements retry logic, circuit breaker, fallback patterns
- Configuration-driven via `config/custodians.php`
- Demo/sandbox mode support

### Recent Africa Service Adapters (Merged Feb 24, 2026)
Commit: `80aa56b5` - "feat: Add Africa service adapters (Smile ID, Flutterwave, Circle CCTP)"

#### Example: Smile ID (Identity Verification)
**Location**: `app/Domain/Compliance/Services/IdentityVerificationService.php`

```php
private array $providers = [
    'smileid' => [
        'endpoint'      => 'https://api.smileidentity.com/v1/',
        'api_key'       => null,
        'partner_id'    => null,
        'signature_key' => null,
    ],
    ...
];

public function createVerificationSession(string $provider, array $userData): array;
public function getVerificationResult(string $provider, string $sessionId): array;
```

Supports African markets with:
- Document verification (job_type=5)
- Liveness checks
- ID authority validation
- Country-specific ID types (NATIONAL_ID, PASSPORT, etc.)

**Test**: `tests/Unit/Domain/Compliance/Services/IdentityVerificationSmileIdTest.php`

#### Example: Flutterwave Connector
**Location**: `app/Domain/Custodian/Connectors/FlutterwaveConnector.php`

```php
class FlutterwaveConnector extends BaseCustodianConnector {
    // Supports NGN, GHS, KES, ZAR, XOF, XAF, TZS, UGX, USD, EUR, GBP
    // Resilience patterns: circuit breaker, retry, fallback
}
```

#### Example: Circle CCTP Bridge Adapter
**Location**: `app/Domain/CrossChain/Services/Adapters/CircleCctpBridgeAdapter.php`

Implements `BridgeAdapterInterface` for USDC cross-chain transfers.

---

## 7. Configuration System

### Compliance Configuration
**Location**: `config/compliance-certification.php`

Controls:
- SOC 2 Type II audit settings
- PCI DSS classification levels and key rotation
- Data residency (EU, US, APAC, UK regions)
- GDPR breach notification timelines (72 hours)
- Security scanning (SAST, DAST, container scanning)

### Service Configuration
**Location**: `config/services.php`

Contains provider credentials:
```php
'smileid' => [
    'api_key'       => env('SMILE_ID_API_KEY'),
    'partner_id'    => env('SMILE_ID_PARTNER_ID'),
    'signature_key' => env('SMILE_ID_SIGNATURE_KEY'),
],
```

### Custodian Configuration
**Location**: `config/custodians.php`

Manages connector configs with:
- Sandbox vs production mode
- API endpoints
- Rate limits
- Timeout settings
- Fallback behavior

### RegTech Configuration
Contains per-regulator endpoints:
```php
'api_endpoints' => [
    'fincen' => [
        'sandbox' => 'https://sandbox.fincen.demo/api/v1',
        'production' => 'https://api.fincen.treasury.gov/v1',
    ],
    ...
]
```

---

## 8. GraphQL Integration

### AML Screening Mutation
**Location**: `app/GraphQL/Mutations/Compliance/TriggerAmlCheckMutation.php`

```php
class TriggerAmlCheckMutation {
    public function __invoke(mixed $rootValue, array $args): ComplianceAlert {
        // Input: entity_id, entity_type, check_type
        // Output: ComplianceAlert
        // Internal: Calls AmlScreeningService->performComprehensiveScreening()
    }
}
```

### GraphQL Schema
- Query: List screenings, alerts, risk profiles
- Mutations: Trigger checks, review results, update statuses
- Subscriptions: Real-time alert notifications (via Redis Streams)

---

## 9. Testing Structure

### Unit Tests
- `tests/Unit/Domain/Compliance/Services/AmlScreeningServiceTest.php` (308 lines)
  - Tests all screening methods
  - Tests risk calculation logic
  - Tests match counting
  - Tests OFAC/PEP/media checks with reflection
  - Tests screening number generation

- `tests/Unit/Domain/Compliance/Services/IdentityVerificationSmileIdTest.php` (72 lines)
  - Tests Smile ID session creation
  - Tests result shape validation
  - Tests country-specific configurations

### Feature Tests
- `tests/Feature/Http/Controllers/Api/V2/ComplianceControllerTest.php`
- `tests/Feature/GraphQL/ComplianceGraphQLTest.php`
- `tests/Feature/AgentProtocol/AgentComplianceTest.php`

### Integration Tests
- `tests/Integration/GraphQL/ComplianceGraphQLTest.php`
- `tests/Integration/Compliance/ComplianceCertificationTest.php`

---

## 10. CHAINALYSIS ADAPTER - IMPLEMENTED

**Status**: Task #9 "Build Chainalysis compliance/sanctions adapter" is **COMPLETED** (Feb 25, 2026)

The codebase now has:
- SanctionsScreeningInterface (app/Domain/Compliance/Contracts/)
- InternalSanctionsAdapter (fallback with simulated OFAC/EU/UN checks)
- ChainalysisAdapter (real API integration via Sanctions Screening API v2)
- ComplianceServiceProvider (auto-selects adapter based on config)
- AmlScreeningService now uses adapter pattern with constructor injection
- 20 new tests (16 Chainalysis, 4 adapter delegation)
- Config in services.php, env vars in .env.example and .env.production.example

## 11. Architecture Decision: Where Chainalysis Should Live

### Option A: Extend AmlScreeningService (Simplest)
- Add `ChainalysisAdapter` as a provider choice
- Modify `performSanctionsCheck()` to use adapter when configured
- Follow pattern: `if ($provider === 'chainalysis') { /* API call */ }`

### Option B: Create Dedicated Sanctions Adapter Interface (Recommended)
Create `app/Domain/Compliance/Contracts/SanctionsScreeningAdapterInterface`:
```php
interface SanctionsScreeningAdapterInterface {
    public function getName(): string;
    public function getSupportedListTypes(): array;  // sanctions, pep, adverse_media
    public function screenEntity(array $entity): array;  // Returns results
    public function validateConfiguration(): bool;
    public function getApiEndpoint(): string;
    public function isSandboxMode(): bool;
}
```

Implement:
- `app/Domain/Compliance/Adapters/ChainalysisAdapter` - Production integration
- `app/Domain/Compliance/Adapters/InternalMockAdapter` - Current demo lists
- `app/Domain/Compliance/Adapters/OfacDirectAdapter` - Direct OFAC integration (future)

Then modify `AmlScreeningService`:
```php
private SanctionsScreeningAdapterInterface $adapter;

public function __construct(SanctionsScreeningAdapterInterface $adapter) {
    $this->adapter = $adapter;
}

public function performSanctionsCheck(array $searchParams): array {
    return $this->adapter->screenEntity($searchParams);
}
```

### Option C: Follow RegTech Pattern (Most Consistent)
Use existing `RegulatoryFilingAdapterInterface` pattern but for sanctions.
Create `app/Domain/Compliance/Adapters/` directory with:
- `AbstractSanctionsAdapter extends RegulatoryFilingAdapterInterface`
- `ChainalysisAdapter extends AbstractSanctionsAdapter`
- Configuration loading from `config/sanctions.php`

---

## 12. Configuration File Structure for Chainalysis

### `config/sanctions.php` (New File)
```php
return [
    'demo_mode' => env('SANCTIONS_DEMO_MODE', true),
    'default_provider' => env('SANCTIONS_PROVIDER', 'internal'),
    
    'providers' => [
        'chainalysis' => [
            'api_key' => env('CHAINALYSIS_API_KEY'),
            'api_url' => env('CHAINALYSIS_API_URL', 'https://api.chainalysis.com'),
            'sandbox_url' => env('CHAINALYSIS_SANDBOX_URL', 'https://sandbox.chainalysis.com'),
            'supported_lists' => ['sanctions', 'pep', 'adverse_media'],
            'timeout' => 30,
            'retry_attempts' => 3,
            'cache_ttl' => 3600,
        ],
        'internal' => [
            'supported_lists' => ['sanctions', 'pep', 'adverse_media'],
        ],
        'ofac' => [
            'api_url' => env('OFAC_API_URL', 'https://api.ofac.treasury.gov/v1'),
            'supported_lists' => ['sanctions'],
            'timeout' => 30,
        ],
    ],
];
```

### `.env.example` Additions
```
SANCTIONS_PROVIDER=chainalysis
SANCTIONS_DEMO_MODE=true
CHAINALYSIS_API_KEY=your-key-here
CHAINALYSIS_API_URL=https://api.chainalysis.com
CHAINALYSIS_SANDBOX_URL=https://sandbox.chainalysis.com
```

---

## 13. Expected Response Shape

### Chainalysis Screening Result Format
```php
[
    'provider' => 'chainalysis',
    'lists_checked' => ['Chainalysis Sanctions List', 'Chainalysis PEP List', ...],
    'matches' => [
        'chainalysis_sanctions' => [
            [
                'entity_id' => 'ch-123456',
                'name' => 'John Doe',
                'match_score' => 95,
                'list_type' => 'sanctions',
                'designation' => 'SDN',
                'programs' => ['OFAC/IRGC'],
                'details' => [...],
                'risk_level' => 'critical',
            ],
        ],
        'chainalysis_pep' => [...],
    ],
    'total_matches' => 1,
    'risk_assessment' => [
        'overall_risk' => 'critical',
        'confidence_score' => 0.98,
        'recommendation' => 'BLOCK',
    ],
    'timestamp' => '2026-02-25T10:30:00Z',
    'request_id' => 'ch-req-123456789',
]
```

---

## 14. Entry Points to Modify for Chainalysis Integration

1. **AmlScreeningService.php** (lines 22-26, 150-184, 292-324)
   - Modify sanctions list configuration
   - Update `performSanctionsCheck()` to use adapter

2. **AmlScreening model** 
   - May need new fields for Chainalysis-specific data

3. **Configuration files**
   - Create `config/sanctions.php`
   - Update `.env.example`

4. **Tests**
   - Add `ChainalysisAdapterTest`
   - Add integration tests calling Chainalysis

5. **GraphQL**
   - May add screening provider selection parameter

---

## 15. File Locations Reference

### Core Compliance Infrastructure
```
app/Domain/Compliance/
├── Aggregates/
│   ├── AmlScreeningAggregate.php         ← Event sourcing root
│   ├── ComplianceAlertAggregate.php
│   └── TransactionMonitoringAggregate.php
├── Models/
│   ├── AmlScreening.php                  ← Main model
│   ├── AmlScreeningEvent.php
│   ├── CustomerRiskProfile.php
│   └── ComplianceAlert.php
├── Services/
│   ├── AmlScreeningService.php           ← PRIMARY SERVICE (integrate here)
│   ├── ComplianceService.php
│   ├── IdentityVerificationService.php
│   ├── CustomerRiskService.php
│   └── EnhancedKycService.php
├── Events/
│   ├── AmlScreeningStarted.php
│   ├── AmlScreeningCompleted.php
│   ├── ScreeningMatchFound.php
│   └── ... (40+ event classes)
├── Repositories/
│   └── AmlScreeningRepository.php
├── Projectors/
│   └── AmlScreeningProjector.php
└── Routes/
    └── api.php                           ← Endpoint definitions
```

### Adapter References
```
app/Domain/RegTech/
├── Adapters/
│   ├── AbstractRegulatoryAdapter.php      ← Template pattern
│   ├── FinCENAdapter.php
│   ├── FCAAdapter.php
│   ├── ESMAAdapter.php
│   └── MASAdapter.php
├── Contracts/
│   └── RegulatoryFilingAdapterInterface.php ← Interface pattern
```

### Configuration
```
config/
├── compliance-certification.php    ← SOC2, PCI DSS, GDPR config
├── services.php                    ← Provider credentials
├── custodians.php                  ← Bank connector config
├── agent_protocol.php              ← AI compliance config
```

---

## 16. Key Design Patterns to Follow

1. **Adapter Pattern** - All external services use adapters
2. **Event Sourcing** - All screening events are persisted
3. **CQRS** - Separate read/write models (aggregate + projectors)
4. **Configuration-Driven** - All credentials/endpoints in config
5. **Demo/Sandbox Mode** - All adapters support testing without real API
6. **Resilience** - Retry, circuit breaker, fallback support
7. **Logging** - All API interactions logged via Laravel logging
8. **Immutable Aggregate State** - Event reconstructs state

---

## 17. Task #9 Implementation Checklist

- [ ] Create `ChainalysisAdapter` implementing SanctionsScreeningAdapterInterface
- [ ] Add Chainalysis configuration to `config/sanctions.php`
- [ ] Update `AmlScreeningService` to use adapter pattern
- [ ] Add Chainalysis API response parsing
- [ ] Implement demo/sandbox mode for Chainalysis
- [ ] Create Chainalysis risk scoring mapping
- [ ] Add unit tests for ChainalysisAdapter
- [ ] Add feature tests for AML screening with Chainalysis
- [ ] Update GraphQL mutations to accept provider selection
- [ ] Update `.env.example` with Chainalysis credentials
- [ ] Add Chainalysis documentation to API docs
- [ ] Create migration for any new AmlScreening fields if needed

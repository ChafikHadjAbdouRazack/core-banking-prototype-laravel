# GitHub Actions Cleanup Summary

## Removed Workflows (8 files)

The following duplicate/redundant workflows were removed:

### 1. **ci-old.yml.bak**
- **Reason**: Outdated backup file
- **Replaced by**: ci-pipeline.yml

### 2. **ci-simple.yml**
- **Reason**: Simplified CI that duplicates main pipeline functionality
- **Replaced by**: ci-pipeline.yml

### 3. **performance.yml**
- **Reason**: Standalone performance testing
- **Replaced by**: 05-performance.yml (modular workflow)

### 4. **performance-testing.yml**
- **Reason**: Duplicate performance testing with k6
- **Replaced by**: 05-performance.yml (includes k6 tests)

### 5. **security.yml**
- **Reason**: Basic Gitleaks-only security check
- **Replaced by**: 02-security-scan.yml (comprehensive security scanning)

### 6. **security-scanning.yml**
- **Reason**: Duplicate comprehensive security scanning
- **Replaced by**: 02-security-scan.yml (modular workflow)

### 7. **test-coverage.yml**
- **Reason**: Standalone coverage analysis
- **Replaced by**: Coverage reporting in 03-test-suite.yml

### 8. **test-coverage-minimal.yml**
- **Reason**: Minimal coverage for feature branches
- **Replaced by**: Coverage in 03-test-suite.yml

## Retained Workflows (13 files)

### Core CI Pipeline (7 files)
- **ci-pipeline.yml** - Main orchestrator
- **01-code-quality.yml** - Code standards & static analysis
- **02-security-scan.yml** - Security scanning
- **03-test-suite.yml** - All tests including Behat
- **04-security-tests.yml** - Security test suite
- **05-performance.yml** - Performance testing
- **06-build.yml** - Asset building

### Auxiliary Workflows (5 files)
- **behat-tests.yml** - Quick Behat testing on feature changes
- **claude.yml** - AI assistant integration
- **database-operations.yml** - DB management tasks
- **deploy.yml** - Deployment automation
- **test-matrix.yml** - Test summary reporting

### Documentation (1 file)
- **README.md** - Comprehensive workflow documentation

## Benefits of Cleanup

1. **Reduced Confusion**: Clear separation between main CI and auxiliary workflows
2. **No Duplicate Runs**: Eliminated redundant test executions
3. **Faster CI**: No overlapping security scans or performance tests
4. **Clearer Purpose**: Each workflow has a distinct, documented purpose
5. **Maintainability**: Modular workflows are easier to update and debug

## Migration Notes

If you were using any of the removed workflows directly:

- Replace `performance.yml` triggers with `05-performance.yml`
- Replace `security-scanning.yml` with `02-security-scan.yml`
- Replace `test-coverage.yml` with the coverage from `03-test-suite.yml`
- Use `ci-pipeline.yml` for comprehensive CI/CD needs

## Recommended Workflow Usage

- **For PRs**: Automatic trigger of `ci-pipeline.yml`
- **For Behat development**: Manual trigger of `behat-tests.yml`
- **For deployments**: Use `deploy.yml` with appropriate approvals
- **For DB operations**: Manual trigger of `database-operations.yml`
# Database Migration Changelog

## [2025-06-12] Add Frozen Column to Accounts Table

### Migration: `2025_06_12_204401_add_frozen_column_to_accounts_table.php`

#### Purpose
Adds support for account freezing/unfreezing functionality to prevent transactions on compromised or suspicious accounts.

#### Changes
- Added `frozen` boolean column to `accounts` table (default: false)
- Added index on `frozen` column for query performance

#### Impact
- Enables compliance features for account suspension
- Prevents deposits, withdrawals, and transfers on frozen accounts
- Supports regulatory requirements for fraud prevention

#### Related Files Updated
- `app/Http/Controllers/Api/AccountController.php` - Added frozen status checks
- `app/Http/Controllers/Api/TransactionController.php` - Added frozen validation
- `app/Http/Controllers/Api/TransferController.php` - Added frozen validation  
- `app/Http/Controllers/Api/BalanceController.php` - Shows frozen status
- `app/Domain/Account/Projectors/AccountProjector.php` - Handles freeze/unfreeze events
- `app/Domain/Account/Actions/FreezeAccount.php` - New action for freezing
- `app/Domain/Account/Actions/UnfreezeAccount.php` - New action for unfreezing
- `database/factories/AccountFactory.php` - Added frozen field support
- `tests/Feature/Api/AccountFreezeTest.php` - Comprehensive test coverage

#### Running the Migration
```bash
php artisan migrate
```

#### Rolling Back
```bash
php artisan migrate:rollback
```
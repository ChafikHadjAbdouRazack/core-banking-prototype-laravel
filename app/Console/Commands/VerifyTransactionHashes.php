<?php

namespace App\Console\Commands;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\Events\HasHash;
use App\Domain\Account\Exceptions\InvalidHashException;
use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Account\Repositories\TransactionRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyTransactionHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-transaction-hashes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the hashes of all transaction events to ensure data integrity';

    /**
     * @param \App\Domain\Account\Repositories\TransactionRepository $transactionRepository
     * @param \App\Domain\Account\Repositories\AccountRepository $accountRepository
     * @param array $erroneous_accounts
     * @param array $erroneous_transactions
     */
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected AccountRepository     $accountRepository,
        protected array                 $erroneous_accounts = [],
        protected array                 $erroneous_transactions = [],
    ) {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $this->info( 'Verifying transaction event hashes...' );

        $accounts = $this->accountRepository->getAllByCursor();

        /** @var \App\Models\Account $account */
        foreach ( $accounts as $account )
        {
            $aggregate = TransactionAggregate::retrieve( $account->uuid );

            try
            {
                $this->verifyAggregateHashes( $aggregate );
            }
            catch ( InvalidHashException $e )
            {
                $this->erroneous_accounts[] = $account->uuid;
                $this->error(
                    "Invalid hash found in account {$account->uuid}: " .
                    $e->getMessage()
                );
            }
        }

        if ( count( $this->erroneous_accounts ) === 0 )
        {
            $this->info( 'All accounts and transactions hashes are valid.' );

            return 0; // Success
        }
        else
        {
            $this->error(
                'Some account has transactions which hashes were invalid. Check logs for details.'
            );

            return 1; // Failure
        }
    }

    protected function verifyAggregateHashes( TransactionAggregate $aggregate
    ): void {
        foreach ( $aggregate->getAppliedEvents() as $event )
        {
            if ( $event instanceof HasHash )
            {
                try
                {
                    $aggregate->validateHash( $event->hash, $event->money );
                }
                catch ( InvalidHashException $e )
                {
                    // Log the hash validation error with full context
                    Log::error('Transaction hash validation failed', [
                        'aggregate_uuid' => $aggregate->uuid(),
                        'event_class' => get_class($event),
                        'event_uuid' => method_exists($event, 'aggregateRootUuid') ? $event->aggregateRootUuid() : null,
                        'event_hash' => $event->hash->toString(),
                        'money_amount' => $event->money->getAmount(),
                        'money_currency' => $event->money->getCurrency()->getCode(),
                        'exception_message' => $e->getMessage(),
                        'exception_code' => $e->getCode(),
                        'timestamp' => now()->toISOString(),
                    ]);
                    
                    $this->erroneous_transactions[] = $event;

                    throw $e;
                }

            }
        }
    }
}

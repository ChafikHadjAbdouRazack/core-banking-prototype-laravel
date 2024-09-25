<?php

namespace App\Domain\Account\Services;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\Aggregates\TransferAggregate;
use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use Workflow\WorkflowStub;

class AccountService
{
    /**
     * @param \App\Domain\Account\Aggregates\LedgerAggregate $ledger
     * @param \App\Domain\Account\Aggregates\TransactionAggregate $transaction
     * @param \App\Domain\Account\Aggregates\TransferAggregate $transfer
     */
    public function __construct(
        protected LedgerAggregate      $ledger,
        protected TransactionAggregate $transaction,
        protected TransferAggregate    $transfer
    ) {
    }

    /**
     * @param mixed $account
     */
    public function create( Account|array $account ): void
    {
        $workflow = WorkflowStub::make( CreateAccountWorkflow::class );
        $workflow->start( __account( $account ) );
    }

    /**
     * @param mixed $uuid
     *
     * @return void
     */
    public function destroy( mixed $uuid ): void
    {
        $this->ledger->retrieve( __account_uuid( $uuid ) )
                     ->deleteAccount()
                     ->persist();
    }

    /**
     * @param mixed $uuid
     * @param mixed $amount
     *
     * @return void
     */
    public function deposit( mixed $uuid, mixed $amount ): void
    {
        $this->transaction->retrieve( __account_uuid( $uuid ) )
                          ->credit( __money( $amount ) )
                          ->persist();
    }

    /**
     * @param mixed $uuid
     * @param mixed $amount
     *
     * @return void
     */
    public function withdraw( mixed $uuid, mixed $amount ): void
    {
        $this->transaction->retrieve( __account_uuid( $uuid ) )
                          ->debit( __money( $amount ) )
                          ->persist();
    }

    /**
     * @param mixed $from
     * @param mixed $to
     * @param mixed $amount
     *
     * @return void
     */
    public function transfer( mixed $from, mixed $to, mixed $amount ): void
    {
        $debiting = $this->transfer->loadUuid( __account_uuid( $from ) )
                                      ->debit( __money( $amount ) );

        $crediting = $this->transfer->loadUuid( __account_uuid( $to ) )
                                       ->credit( __money( $amount ) );

        $debiting->persist();
        $crediting->persist();
    }
}

<?php

namespace App\Domain\Account\Repositories;

use App\Models\Account;
use App\Models\Turnover;
use DateTimeInterface;

final class TurnoverRepository
{
    public function __construct(
        protected Turnover $turnover
    ) {
    }

    /**
     * @param string $accountUuid
     * @param \DateTimeInterface $date
     *
     * @return \App\Models\Turnover|null
     */
    public function findByAccountAndDate(
        Account           $account,
        DateTimeInterface $date
    ): ?Turnover {
        return $this->turnover
            ->whereBelongsTo( $account )
            ->where( 'date', $date->toDateString() )
            ->first();
    }

    /**
     * @param \DateTimeInterface $date
     * @param string $accountUuid
     * @param int $amount
     *
     * @return \App\Models\Turnover
     */
    public function incrementForDateById(
        DateTimeInterface $date, string $accountUuid, int $amount
    ): Turnover {
        $turnover = $this->turnover->firstOrCreate(
            [
                'date'         => $date->toDateString(),
                'account_uuid' => $accountUuid,
            ],
            [
                'account_uuid' => $accountUuid,
                'count'        => 0,
                'amount'       => 0,
                'debit'        => 0,
                'credit'       => 0,
            ]
        );

        return $this->updateTurnover( $turnover, $amount );
    }

    /**
     * @param \App\Models\Turnover $turnover
     * @param int $amount
     *
     * @return \App\Models\Turnover
     */
    protected function updateTurnover( Turnover $turnover, int $amount
    ): Turnover {
        $turnover->count += 1;
        $turnover->amount += $amount;
        
        // Update debit/credit fields for proper accounting
        if ($amount > 0) {
            $turnover->credit += $amount;
        } else {
            $turnover->debit += abs($amount);
        }

        // Save the changes in a single query
        $turnover->save();

        return $turnover;
    }
}

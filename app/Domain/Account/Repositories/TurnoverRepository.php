<?php

namespace App\Domain\Account\Repositories;

use App\Domain\Account\Models\Turnover;
use App\Models\Account;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class TurnoverRepository
{
    public function __construct(
        protected Turnover $turnover
    ) {
    }

    /**
     * @param  string  $accountUuid
     */
    public function findByAccountAndDate(
        Account $account,
        DateTimeInterface $date
    ): ?Turnover {
        return Turnover::where('account_uuid', $account->uuid)
            ->where('date', $date->toDateString())
            ->first();
    }

    public function incrementForDateById(
        DateTimeInterface $date,
        string $accountUuid,
        int $amount
    ): Turnover {
        return DB::transaction(
            function () use ($date, $accountUuid, $amount) {
                // Use updateOrCreate with lock for atomic operation
                $turnover = Turnover::lockForUpdate()->updateOrCreate(
                    [
                        'date'         => $date->toDateString(),
                        'account_uuid' => $accountUuid,
                    ],
                    [
                        'count'  => 0,
                        'amount' => 0,
                        'debit'  => 0,
                        'credit' => 0,
                    ]
                );

                return $this->updateTurnover($turnover, $amount);
            }
        );
    }

    /**
     * @param Turnover $turnover
     * @param int $amount
     * @return Turnover
     */
    protected function updateTurnover(Turnover $turnover, int $amount): Turnover
    {
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

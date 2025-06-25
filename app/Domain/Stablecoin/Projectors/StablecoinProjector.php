<?php

declare(strict_types=1);

namespace App\Domain\Stablecoin\Projectors;

use App\Domain\Account\Services\AccountService;
use App\Domain\Stablecoin\Events\CollateralLocked;
use App\Domain\Stablecoin\Events\CollateralPositionClosed;
use App\Domain\Stablecoin\Events\CollateralPositionCreated;
use App\Domain\Stablecoin\Events\CollateralPositionLiquidated;
use App\Domain\Stablecoin\Events\CollateralPositionUpdated;
use App\Domain\Stablecoin\Events\CollateralReleased;
use App\Domain\Stablecoin\Events\StablecoinBurned;
use App\Domain\Stablecoin\Events\StablecoinMinted;
use App\Models\Account;
use App\Models\StablecoinCollateralPosition;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class StablecoinProjector extends Projector
{
    public function onCollateralPositionCreated(CollateralPositionCreated $event): void
    {
        StablecoinCollateralPosition::create([
            'uuid' => $event->position_uuid,
            'account_uuid' => $event->account_uuid,
            'stablecoin_code' => $event->stablecoin_code,
            'collateral_asset_code' => $event->collateral_asset_code,
            'collateral_amount' => $event->collateral_amount,
            'debt_amount' => $event->debt_amount,
            'collateral_ratio' => $event->collateral_ratio,
            'status' => $event->status,
        ]);
    }

    public function onCollateralLocked(CollateralLocked $event): void
    {
        // Subtract collateral from account balance
        $account = Account::where('uuid', $event->account_uuid)->firstOrFail();
        $account->subtractBalance($event->collateral_asset_code, $event->amount);

        // Update position collateral amount
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->increment('collateral_amount', $event->amount);
    }

    public function onStablecoinMinted(StablecoinMinted $event): void
    {
        // Add stablecoin to account balance
        $account = Account::where('uuid', $event->account_uuid)->firstOrFail();
        $account->addBalance($event->stablecoin_code, $event->amount);

        // Update position debt amount
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->increment('debt_amount', $event->amount);
    }

    public function onStablecoinBurned(StablecoinBurned $event): void
    {
        // Remove stablecoin from account balance
        $account = Account::where('uuid', $event->account_uuid)->firstOrFail();
        $account->subtractBalance($event->stablecoin_code, $event->amount);

        // Update position debt amount
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->decrement('debt_amount', $event->amount);
    }

    public function onCollateralReleased(CollateralReleased $event): void
    {
        // Return collateral to account balance
        $account = Account::where('uuid', $event->account_uuid)->firstOrFail();
        $account->addBalance($event->collateral_asset_code, $event->amount);

        // Update position collateral amount
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->decrement('collateral_amount', $event->amount);
    }

    public function onCollateralPositionUpdated(CollateralPositionUpdated $event): void
    {
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->update([
            'collateral_amount' => $event->collateral_amount,
            'debt_amount' => $event->debt_amount,
            'collateral_ratio' => $event->collateral_ratio,
            'status' => $event->status,
        ]);
    }

    public function onCollateralPositionClosed(CollateralPositionClosed $event): void
    {
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function onCollateralPositionLiquidated(CollateralPositionLiquidated $event): void
    {
        $position = StablecoinCollateralPosition::where('uuid', $event->position_uuid)->firstOrFail();
        $position->update([
            'status' => 'liquidated',
            'liquidated_at' => now(),
            'collateral_amount' => 0,
            'debt_amount' => 0,
        ]);
    }
}
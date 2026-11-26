<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;

/**
 * Reactivates a deactivated account from the admin panel, as its owner's sign-in would, so it is not
 * closed when its deadline passes. Unlike a sign-in, it also reactivates an account whose deadline has
 * passed but which is not closed yet (ADR 0009).
 */
final readonly class ReactivateAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Reactivates the account and records it; an active account is left as it is.
     *
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $id): void
    {
        $account = $this->accounts->get($id);

        if ($account->isActive()) {
            return;
        }

        $account->reactivate();
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::Reactivated, $this->clock->now());
    }
}

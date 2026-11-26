<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;

/**
 * Reactivates a deactivated account from the admin panel, as its owner's sign-in would, so it is not
 * closed when its deadline passes.
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

        $this->accounts->save(new Account(
            $account->id,
            $account->details(),
            $account->access()->withReactivationDeadline(null),
            $account->profile(),
        ));
        $this->activity->record($id, ActivityType::Reactivated, $this->clock->now());
    }
}

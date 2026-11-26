<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\OwnAccountRefused;

/**
 * Deactivates someone's account from the admin panel (the 2015 `AtivarDesativar.php`), with no
 * password: the 30 days of the owner's own deactivation, and the same sign-in brings it back.
 */
final readonly class DeactivateAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Deactivates the account and records it; an account already deactivated keeps its deadline.
     *
     * @throws OwnAccountRefused When the administrator names their own account.
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $administrator, AccountId $id): void
    {
        if ($administrator->value === $id->value) {
            throw OwnAccountRefused::for($id);
        }

        $account = $this->accounts->get($id);

        if (! $account->isActive()) {
            return;
        }

        $now = $this->clock->now();
        $account->deactivate($now);
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::Deactivated, $now);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\WrongPassword;
use SensitiveParameter;

/**
 * Deactivates the owner's account for 30 days, confirmed with the password; ending the session is the caller's.
 */
final readonly class DeactivateAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Deactivates the account and records it.
     *
     * @throws AccountNotFound When the account is not open.
     * @throws WrongPassword When the password is wrong.
     */
    public function handle(AccountId $id, #[SensitiveParameter] string $password): void
    {
        $account = $this->accounts->get($id);

        if (! $this->hasher->matches($password, $account->access()->password)) {
            throw WrongPassword::create();
        }

        $now = $this->clock->now();
        $account->deactivate($now);
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::Deactivated, $now);
    }
}

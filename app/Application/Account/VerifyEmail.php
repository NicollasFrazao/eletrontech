<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;

/**
 * Confirms the account's email address, once; checking the signed link is the caller's.
 */
final readonly class VerifyEmail
{
    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Confirms the email and records it; a second confirmation changes nothing.
     *
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $id): void
    {
        $account = $this->accounts->get($id);

        if ($account->access()->emailVerifiedAt !== null) {
            return;
        }

        $now = $this->clock->now();
        $account->verifyEmail($now);
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::EmailVerified, $now);
    }
}

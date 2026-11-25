<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\ImageStore;

/**
 * Closes an account whose reactivation deadline passed: erases what identifies the person, deletes the
 * pictures and records it.
 */
final readonly class AccountCloser
{
    public function __construct(
        private AccountRepository $accounts,
        private ImageStore $images,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Closes the account.
     */
    public function close(Account $account): void
    {
        $this->accounts->close($account->id);

        foreach ($account->profile()->images as $path) {
            $this->images->delete($path);
        }

        $this->activity->record($account->id, ActivityType::Closed, $this->clock->now());
    }
}

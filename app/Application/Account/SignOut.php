<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;

/**
 * Records that the owner signed out; ending the session is the caller's.
 */
final readonly class SignOut
{
    public function __construct(
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Records the sign-out.
     */
    public function handle(AccountId $account): void
    {
        $this->activity->record($account, ActivityType::SignedOut, $this->clock->now());
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;

/**
 * The owner's profile with the latest recorded actions.
 */
final readonly class ViewProfile
{
    /**
     * How many actions the profile lists.
     */
    private const int LATEST_ACTIVITY = 10;

    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
    ) {}

    /**
     * The profile.
     *
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $id): ProfileView
    {
        return new ProfileView($this->accounts->get($id), $this->activity->latest($id, self::LATEST_ACTIVITY));
    }
}

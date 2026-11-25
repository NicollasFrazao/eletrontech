<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\Preferences;

/**
 * Changes the night mode and the tutorial toggle.
 */
final readonly class UpdatePreferences
{
    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Stores the preferences.
     *
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $id, Preferences $preferences): void
    {
        $account = $this->accounts->get($id);
        $account->replaceProfile($account->profile()->withPreferences($preferences));
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::PreferencesUpdated, $this->clock->now());
    }
}

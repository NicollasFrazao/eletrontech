<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\PlainPassword;
use SensitiveParameter;

/**
 * Gives an account a new password and records how it was given: the step a change and a reset share.
 */
final readonly class PasswordWriter
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Hashes and stores the new password.
     *
     * @throws InvalidAccountData When the new password is shorter than 8 characters or longer than 72 bytes.
     */
    public function write(Account $account, #[SensitiveParameter] string $newPassword, ActivityType $how): void
    {
        $account->changePassword($this->hasher->hash(PlainPassword::from($newPassword)));
        $this->accounts->save($account);
        $this->activity->record($account->id, $how, $this->clock->now());
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityType;
use App\Domain\Account\InvalidAccountData;
use SensitiveParameter;

/**
 * Sets a new password from a reset link; checking the link's token is the caller's.
 */
final readonly class ResetPassword
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordWriter $passwords,
    ) {}

    /**
     * Stores the new password.
     *
     * @throws AccountNotFound When the account is not open.
     * @throws InvalidAccountData When the new password is not valid.
     */
    public function handle(AccountId $id, #[SensitiveParameter] string $newPassword): void
    {
        $this->passwords->write($this->accounts->get($id), $newPassword, ActivityType::PasswordReset);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityType;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\WrongPassword;
use SensitiveParameter;

/**
 * Changes the password of a signed-in owner, who confirms it with the current one (the 2015 page did not ask).
 */
final readonly class ChangePassword
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
        private PasswordWriter $passwords,
    ) {}

    /**
     * Stores the new password.
     *
     * @throws AccountNotFound When the account is not open.
     * @throws WrongPassword When the current password is wrong.
     * @throws InvalidAccountData When the new password is not valid.
     */
    public function handle(
        AccountId $id,
        #[SensitiveParameter] string $currentPassword,
        #[SensitiveParameter] string $newPassword,
    ): void {
        $account = $this->accounts->get($id);

        if (! $this->hasher->matches($currentPassword, $account->access()->password)) {
            throw WrongPassword::create();
        }

        $this->passwords->write($account, $newPassword, ActivityType::PasswordChanged);
    }
}

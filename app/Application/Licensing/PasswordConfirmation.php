<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\WrongPassword;
use SensitiveParameter;

/**
 * Checks the password a signed-in person types to confirm an action. 2015 compared it in the browser
 * against the decoded password it had written into the page.
 */
final readonly class PasswordConfirmation
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
    ) {}

    /**
     * Passes when the typed text is the account's password.
     *
     * @throws WrongPassword When it is not.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function confirm(AccountId $account, #[SensitiveParameter] string $typed): void
    {
        if (! $this->hasher->matches($typed, $this->accounts->get($account)->access()->password)) {
            throw WrongPassword::create();
        }
    }
}

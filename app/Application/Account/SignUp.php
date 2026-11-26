<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Application\Licensing\GrantInitialLicence;
use App\Domain\Account\AccountId;
use App\Domain\Account\AlreadyRegistered;
use App\Domain\Account\InvalidAccountData;

/**
 * The sign-up as a person sees it: the account is registered and starts with the free package for seven
 * days (as in 2015).
 */
final readonly class SignUp
{
    public function __construct(
        private RegisterAccount $register,
        private GrantInitialLicence $grant,
    ) {}

    /**
     * Registers the account and grants its initial licence.
     *
     * @throws InvalidAccountData When a value is not valid.
     * @throws AlreadyRegistered When an open account holds the email or the CPF.
     */
    public function handle(RegistrationInput $input): AccountId
    {
        $id = $this->register->handle($input);
        $this->grant->handle($id);

        return $id;
    }
}

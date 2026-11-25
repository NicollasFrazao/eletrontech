<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A sign-in failed. The same error for an unknown email, a wrong password and an account closed by
 * its deadline, so the answer does not tell which emails have an account.
 */
final class InvalidCredentials extends AccountError
{
    /**
     * The error, without saying why.
     */
    public static function create(): self
    {
        return new self('The email or the password is wrong.');
    }
}

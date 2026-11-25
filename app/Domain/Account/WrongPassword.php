<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A signed-in person confirmed an action with a password that is not the account's.
 */
final class WrongPassword extends AccountError
{
    /**
     * The error, without the typed text.
     */
    public static function create(): self
    {
        return new self('The current password is wrong.');
    }
}

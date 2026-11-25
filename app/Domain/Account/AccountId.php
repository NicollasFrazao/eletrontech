<?php

declare(strict_types=1);

namespace App\Domain\Account;

use InvalidArgumentException;

/**
 * The identity of an account: a positive integer the storage assigns when the account is registered.
 */
final readonly class AccountId
{
    /**
     * @throws InvalidArgumentException When the value is not positive (a programming error, not user input).
     */
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException("An account id is positive; {$value} given.");
        }
    }
}

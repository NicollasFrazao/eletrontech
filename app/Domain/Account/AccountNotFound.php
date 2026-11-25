<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * No open account has that id.
 */
final class AccountNotFound extends AccountError
{
    /**
     * The error for one id.
     */
    public static function withId(AccountId $id): self
    {
        return new self("No open account has the id {$id->value}.");
    }
}

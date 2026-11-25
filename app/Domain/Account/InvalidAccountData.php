<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A value typed for an account (a name, an email, a CPF, a phone, a password) is not valid; the field
 * names which one.
 */
final class InvalidAccountData extends AccountError
{
    private function __construct(
        public readonly AccountField $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * The error for one field, with the reason in words for the log.
     */
    public static function of(AccountField $field, string $reason): self
    {
        return new self($field, "Invalid {$field->value}: {$reason}.");
    }
}

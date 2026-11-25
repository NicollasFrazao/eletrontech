<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * An open account already holds the email or the CPF of a sign-up.
 */
final class AlreadyRegistered extends AccountError
{
    private function __construct(public readonly AccountField $field)
    {
        parent::__construct("An account already holds this {$field->value}.");
    }

    /**
     * The error for the email.
     */
    public static function email(): self
    {
        return new self(AccountField::Email);
    }

    /**
     * The error for the CPF.
     */
    public static function cpf(): self
    {
        return new self(AccountField::Cpf);
    }
}

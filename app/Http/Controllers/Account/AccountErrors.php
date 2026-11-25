<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Domain\Account\AccountField;
use App\Domain\Account\AlreadyRegistered;
use App\Domain\Account\InvalidAccountData;
use Illuminate\Validation\ValidationException;

/**
 * Turns the account errors a form can cause into a validation error on the form field they concern.
 */
final class AccountErrors
{
    /**
     * The validation error for a refused value or a taken email or CPF.
     */
    public static function toValidation(InvalidAccountData|AlreadyRegistered $error): ValidationException
    {
        $key = $error instanceof AlreadyRegistered ? 'taken' : 'invalid';

        return ValidationException::withMessages([
            self::formField($error->field) => __("accounts.errors.{$key}.{$error->field->value}"),
        ]);
    }

    /**
     * The name of the form field that holds an account value.
     */
    private static function formField(AccountField $field): string
    {
        return $field === AccountField::Phone ? 'phone_number' : $field->value;
    }
}

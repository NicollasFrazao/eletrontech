<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Application\Account\RegisterAccount;
use App\Application\Account\VerifyEmail;
use App\Domain\Account\AccountId;
use App\Infrastructure\Persistence\Eloquent\User;

/**
 * Accounts made through the real use cases, for the feature tests.
 */
final class SignedUp
{
    /**
     * An account whose email is confirmed: ana@example.com, password "correct horse".
     */
    public static function verified(string $email = 'ana@example.com', string $cpf = '529.982.247-25'): User
    {
        $user = self::unverified($email, $cpf);
        app(VerifyEmail::class)->handle(new AccountId($user->id));

        return $user->refresh();
    }

    /**
     * An account that has not confirmed its email yet.
     */
    public static function unverified(string $email = 'ana@example.com', string $cpf = '529.982.247-25'): User
    {
        $id = app(RegisterAccount::class)->handle(AccountKit::input($email, $cpf));

        return User::query()->findOrFail($id->value);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Domain\Account\AccountId;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * The account of the session, for the routes behind the `auth` middleware.
 */
final class SignedInAccount
{
    /**
     * The signed-in account's id.
     *
     * @throws AuthenticationException When nobody is signed in (a route outside `auth`).
     */
    public static function id(Request $request): AccountId
    {
        $id = $request->user()?->getAuthIdentifier();

        return is_int($id) ? new AccountId($id) : throw new AuthenticationException;
    }
}

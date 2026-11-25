<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * What signing in does to an account, besides opening the session.
 */
enum SignInEffect
{
    /** The account was active; nothing changes. */
    case None;

    /** The account was deactivated and the deadline has not passed: it is active again. */
    case Reactivated;

    /** The account was deactivated and the deadline has passed: it is closed, and the sign-in fails. */
    case Expired;
}

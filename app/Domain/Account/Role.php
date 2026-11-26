<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;

/**
 * What an account may do beyond the tools: a member uses the application, an administrator also runs
 * the admin panel (the 2015 `ic_admin`).
 */
enum Role: string
{
    case Member = 'member';
    case Administrator = 'administrator';

    /**
     * Whether an account with this role and this access may enter the admin panel: an administrator
     * whose email is confirmed, so an address no one proved to own never opens it.
     */
    public function mayAdminister(Access $access): bool
    {
        return $this === self::Administrator && $access->emailVerifiedAt instanceof DateTimeImmutable;
    }
}

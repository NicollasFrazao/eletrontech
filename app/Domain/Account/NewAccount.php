<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * What a sign-up hands to the storage: the person and how they will sign in.
 */
final readonly class NewAccount
{
    public function __construct(
        public PersonalDetails $details,
        public Access $access,
    ) {}
}

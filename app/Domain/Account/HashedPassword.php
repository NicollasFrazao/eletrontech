<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A password hash, the only form a password is kept in; it cannot be turned back into the password.
 */
final readonly class HashedPassword
{
    public function __construct(public string $value) {}
}

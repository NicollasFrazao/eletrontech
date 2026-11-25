<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Domain\Account\HashedPassword;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\PlainPassword;

/**
 * A readable stand-in for bcrypt: the hash is the password with a prefix. Counts the decoy checks.
 */
final class PrefixHasher implements PasswordHasher
{
    public int $decoyChecks = 0;

    public function hash(PlainPassword $password): HashedPassword
    {
        return new HashedPassword('hashed:'.$password->value);
    }

    public function matches(string $typed, HashedPassword $hash): bool
    {
        return $hash->value === 'hashed:'.$typed;
    }

    public function spendCheck(string $typed): void
    {
        $this->decoyChecks++;
    }
}

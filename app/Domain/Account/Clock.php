<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;

/**
 * The current time, behind an interface so a test can fix it.
 */
interface Clock
{
    /**
     * Now.
     */
    public function now(): DateTimeImmutable;
}

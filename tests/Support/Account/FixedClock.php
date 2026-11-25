<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Domain\Account\Clock;
use DateTimeImmutable;

/**
 * A clock that says what the test sets.
 */
final class FixedClock implements Clock
{
    public function __construct(public DateTimeImmutable $now = new DateTimeImmutable('2026-11-25 10:00:00')) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Moves the clock forward.
     */
    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}

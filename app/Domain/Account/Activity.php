<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;

/**
 * One recorded action of an account.
 */
final readonly class Activity
{
    public function __construct(
        public ActivityType $type,
        public DateTimeImmutable $occurredAt,
    ) {}
}

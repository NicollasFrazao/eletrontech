<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use DateTimeImmutable;

/**
 * The time the use cases act at, and the log they record their actions in, taken together because
 * every use case that records an action needs both.
 */
final readonly class ActivityRecorder
{
    public function __construct(
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Now.
     */
    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    /**
     * Records an action of the account, now.
     */
    public function record(AccountId $account, ActivityType $type): void
    {
        $this->activity->record($account, $type, $this->clock->now());
    }
}

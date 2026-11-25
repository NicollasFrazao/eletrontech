<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\Activity;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use DateTimeImmutable;

/**
 * The activity log in an array, for the use-case tests.
 */
final class InMemoryActivityLog implements ActivityLog
{
    /** @var list<array{int, Activity}> */
    private array $entries = [];

    public function record(AccountId $account, ActivityType $type, DateTimeImmutable $at): void
    {
        $this->entries[] = [$account->value, new Activity($type, $at)];
    }

    public function latest(AccountId $account, int $limit): array
    {
        $mine = array_values(array_map(
            static fn (array $entry): Activity => $entry[1],
            array_filter($this->entries, static fn (array $entry): bool => $entry[0] === $account->value),
        ));

        return array_slice(array_reverse($mine), 0, $limit);
    }

    /**
     * The types recorded for an account, in the order they were recorded.
     *
     * @return list<ActivityType>
     */
    public function typesOf(AccountId $account): array
    {
        $newestFirst = $this->latest($account, PHP_INT_MAX);

        return array_map(static fn (Activity $activity): ActivityType => $activity->type, array_reverse($newestFirst));
    }
}

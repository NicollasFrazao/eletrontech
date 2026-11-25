<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Account\Activity;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Infrastructure\Persistence\Eloquent\ActivityRecord;
use DateTimeImmutable;

/**
 * The activity log kept in `activities`.
 */
final readonly class EloquentActivityLog implements ActivityLog
{
    /**
     * Writes one row for the action.
     */
    public function record(AccountId $account, ActivityType $type, DateTimeImmutable $at): void
    {
        ActivityRecord::query()->create([
            'user_id' => $account->value,
            'type' => $type->value,
            'occurred_at' => $at,
        ]);
    }

    /**
     * The latest rows of the account, newest first (by time, then by insertion).
     *
     * @param  positive-int  $limit
     *
     * @return list<Activity>
     */
    public function latest(AccountId $account, int $limit): array
    {
        return array_values(
            ActivityRecord::query()
                ->where('user_id', $account->value)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(static fn (ActivityRecord $record): Activity => new Activity(
                    ActivityType::from($record->type),
                    $record->occurred_at->toDateTimeImmutable(),
                ))
                ->all(),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;

/**
 * The log of account actions.
 */
interface ActivityLog
{
    /**
     * Records one action.
     */
    public function record(AccountId $account, ActivityType $type, DateTimeImmutable $at): void;

    /**
     * The latest actions of an account, newest first.
     *
     * @param  positive-int  $limit
     *
     * @return list<Activity>
     */
    public function latest(AccountId $account, int $limit): array;
}

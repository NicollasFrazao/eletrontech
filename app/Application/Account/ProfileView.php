<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Account;
use App\Domain\Account\Activity;

/**
 * What the profile page shows: the account and its latest activity.
 */
final readonly class ProfileView
{
    /**
     * @param  list<Activity>  $activity  Newest first.
     */
    public function __construct(
        public Account $account,
        public array $activity,
    ) {}
}

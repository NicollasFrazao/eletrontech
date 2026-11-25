<?php

declare(strict_types=1);

namespace App\Infrastructure\Account;

use App\Domain\Account\Clock;
use DateTimeImmutable;
use Illuminate\Support\Facades\Date;

/**
 * The time of the framework's date factory, which a feature test can freeze (`travelTo`).
 */
final readonly class SystemClock implements Clock
{
    /**
     * Now, as the framework's date factory sees it.
     */
    public function now(): DateTimeImmutable
    {
        return Date::now()->toDateTimeImmutable();
    }
}

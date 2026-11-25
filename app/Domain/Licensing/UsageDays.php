<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * How many days of use a purchase adds: from 7 to 365, as the 2015 shop allowed.
 */
final readonly class UsageDays
{
    /**
     * The fewest days a purchase may add.
     */
    public const int MIN = 7;

    /**
     * The most days a purchase may add.
     */
    public const int MAX = 365;

    /**
     * @throws InvalidUsageDays When the number is outside 7 to 365.
     */
    public function __construct(public int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw InvalidUsageDays::of($value);
        }
    }
}

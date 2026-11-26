<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A purchase asked for fewer than 7 or more than 365 days.
 */
final class InvalidUsageDays extends LicensingError
{
    /**
     * The error for the number of days.
     */
    public static function of(int $days): self
    {
        return new self("A purchase adds from 7 to 365 days; {$days} asked.");
    }
}

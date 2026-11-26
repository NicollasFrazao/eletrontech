<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * What a ready-made package is sold for: its price for its number of days of use. The free package has
 * none, as it is not sold.
 */
final readonly class PackageTerms
{
    public function __construct(
        public Price $price,
        public UsageDays $days,
    ) {}
}

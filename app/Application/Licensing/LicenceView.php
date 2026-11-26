<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Licensing\Licence;
use App\Domain\Licensing\Tool;

/**
 * A person's licence as the licence page shows it: the licence, the days left now, and the names of its tools.
 */
final readonly class LicenceView
{
    /**
     * @param  list<Tool>  $tools  The tools of the package, by name.
     */
    public function __construct(
        public Licence $licence,
        public int $remainingDays,
        public array $tools,
    ) {}
}

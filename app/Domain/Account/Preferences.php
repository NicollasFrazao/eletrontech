<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * How the account wants the interface: the night mode and whether the tutorial is shown. A new
 * account starts with the day theme and the tutorial on, as in 2015 (the columns' defaults).
 */
final readonly class Preferences
{
    public function __construct(
        public bool $isNightMode,
        public bool $showsTutorial,
    ) {}
}

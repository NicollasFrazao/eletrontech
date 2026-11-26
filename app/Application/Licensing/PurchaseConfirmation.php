<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use SensitiveParameter;

/**
 * The part of a purchase the buyer confirms: how many days, and their password.
 */
final readonly class PurchaseConfirmation
{
    public function __construct(
        public int $days,
        #[SensitiveParameter] public string $password,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;

/**
 * A purchase as the shop form sends it: a ready-made package or the tools of a custom one, the days of
 * use and the buyer's password.
 */
final readonly class PurchaseInput
{
    /**
     * @param  int|null  $packageId  The ready-made package; null for a custom package.
     * @param  list<string>  $customTools  The tools of a custom package; ignored for a ready-made one.
     */
    public function __construct(
        public AccountId $buyer,
        public ?int $packageId,
        public array $customTools,
        public PurchaseConfirmation $confirmation,
    ) {}
}

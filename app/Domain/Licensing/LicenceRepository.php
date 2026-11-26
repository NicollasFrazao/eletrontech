<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use App\Domain\Account\AccountId;

/**
 * Where each person's licence is kept: one per person.
 */
interface LicenceRepository
{
    /**
     * The person's licence, running or ended; null when they never had one.
     */
    public function forHolder(AccountId $holder): ?Licence;

    /**
     * Stores the licence, replacing the holder's previous one.
     */
    public function save(Licence $licence): void;
}

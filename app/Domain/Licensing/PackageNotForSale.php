<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A package was used where its kind does not fit: the free package cannot be bought, and only the free
 * package starts a new account's licence.
 */
final class PackageNotForSale extends LicensingError
{
    /**
     * The free package was chosen in the shop.
     */
    public static function free(PackageId $id): self
    {
        return new self("The package {$id->value} is the free one; only a new account gets it.");
    }

    /**
     * A package other than the free one was given to a new account.
     */
    public static function notFree(PackageId $id): self
    {
        return new self("The package {$id->value} is not the free one.");
    }
}

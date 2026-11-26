<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * No package has the id asked for.
 */
final class UnknownPackage extends LicensingError
{
    /**
     * The error for the id.
     */
    public static function withId(int $id): self
    {
        return new self("No package has the id {$id}.");
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * No tool has the identifier asked for.
 */
final class UnknownTool extends LicensingError
{
    /**
     * The error for the identifier.
     */
    public static function withId(string $id): self
    {
        return new self("No tool has the identifier \"{$id}\".");
    }
}

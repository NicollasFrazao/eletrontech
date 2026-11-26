<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A package was defined with no tool besides the everyday ones every package carries.
 */
final class PackageWithoutTools extends LicensingError
{
    /**
     * The error.
     */
    public static function create(): self
    {
        return new self('A package needs at least one tool besides the everyday ones every package carries.');
    }
}

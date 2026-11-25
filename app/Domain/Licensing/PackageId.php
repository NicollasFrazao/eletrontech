<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * The identity of a package: a positive integer the storage assigns.
 */
final readonly class PackageId
{
    /**
     * @throws UnknownPackage When the value is not positive, so no package can have it.
     */
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw UnknownPackage::withId($value);
        }
    }
}

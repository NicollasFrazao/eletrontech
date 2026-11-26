<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A custom package was asked for without any tool.
 */
final class EmptyCustomPackage extends LicensingError
{
    /**
     * The error.
     */
    public static function create(): self
    {
        return new self('A custom package needs at least one tool.');
    }
}

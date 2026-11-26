<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A tool or a package was given a blank name, or one longer than 100 characters.
 */
final class InvalidCatalogueName extends LicensingError
{
    /**
     * The error for the name.
     */
    public static function of(string $name): self
    {
        return new self('A name has from 1 to 100 characters; '.mb_strlen($name).' given.');
    }
}

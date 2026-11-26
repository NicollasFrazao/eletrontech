<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A price was not an amount from 0.00 to 99,999.99 with at most two decimal places.
 */
final class InvalidPrice extends LicensingError
{
    /**
     * The error for the text given.
     */
    public static function of(string $text): self
    {
        return new self("A price is an amount from 0.00 to 99999.99 with at most two decimal places; \"{$text}\" given.");
    }
}

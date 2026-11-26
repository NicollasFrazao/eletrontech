<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * Another tool already has the name; two tools with one name could not be told apart in the shop.
 */
final class ToolNameTaken extends LicensingError
{
    /**
     * The error for the name.
     */
    public static function named(CatalogueName $name): self
    {
        return new self("Another tool is already named \"{$name->value}\".");
    }
}

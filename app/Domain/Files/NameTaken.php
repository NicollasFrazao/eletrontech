<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * A folder already holds a folder or a file with the name.
 */
final class NameTaken extends FilesError
{
    /**
     * The error for the name.
     */
    public static function of(EntryName $name): self
    {
        return new self("This folder already holds something named \"{$name->value}\".");
    }
}

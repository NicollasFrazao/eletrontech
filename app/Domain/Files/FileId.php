<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The identity of a file: a positive integer the storage assigns.
 */
final readonly class FileId
{
    /**
     * @throws FileNotFound When the value is not positive, so no file can have it.
     */
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw FileNotFound::withId($value);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The identity of a folder: a positive integer the storage assigns.
 */
final readonly class FolderId
{
    /**
     * @throws FolderNotFound When the value is not positive, so no folder can have it.
     */
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw FolderNotFound::withId($value);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The folder does not exist or is someone else's.
 */
final class FolderNotFound extends FilesError
{
    /**
     * The error for the id.
     */
    public static function withId(int $id): self
    {
        return new self("No folder of this person has the id {$id}.");
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The file does not exist, is someone else's, or its content is gone.
 */
final class FileNotFound extends FilesError
{
    /**
     * The error for the id.
     */
    public static function withId(int $id): self
    {
        return new self("No file of this person has the id {$id}.");
    }

    /**
     * The error for content missing under a key.
     */
    public static function content(): self
    {
        return new self('The content of the file is missing.');
    }
}

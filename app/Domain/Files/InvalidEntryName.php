<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * A name of a folder or a file is empty, too long, "." or "..", or holds a forbidden character.
 */
final class InvalidEntryName extends FilesError
{
    /**
     * The error for the text, without repeating it (it may hold anything).
     */
    public static function of(string $text): self
    {
        return new self('Invalid name of '.mb_strlen($text).' characters.');
    }
}

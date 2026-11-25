<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * A file is larger than a file may be.
 */
final class FileTooLarge extends FilesError
{
    /**
     * The error for the size.
     */
    public static function of(int $size): self
    {
        return new self("A file of {$size} bytes is larger than ".StorageLimit::MAX_FILE_BYTES.' bytes.');
    }
}

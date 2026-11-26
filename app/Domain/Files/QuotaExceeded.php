<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * A file would take its owner past their quota.
 */
final class QuotaExceeded extends FilesError
{
    /**
     * The error for what is in use and what was added.
     */
    public static function of(int $usage, int $size): self
    {
        return new self("{$usage} bytes are in use; {$size} more would pass ".StorageLimit::QUOTA_BYTES.' bytes.');
    }
}

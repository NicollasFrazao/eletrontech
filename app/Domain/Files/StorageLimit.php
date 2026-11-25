<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * How much the manager keeps: 10 MiB a file and 100 MiB a person.
 */
final readonly class StorageLimit
{
    /**
     * The largest file, in bytes (10 MiB).
     */
    public const int MAX_FILE_BYTES = 10_485_760;

    /**
     * The most a person's files may take, in bytes (100 MiB).
     */
    public const int QUOTA_BYTES = 104_857_600;

    /**
     * Passes when a file of that size fits, given what the person's files take already.
     *
     * @throws FileTooLarge When the file is larger than 10 MiB.
     * @throws QuotaExceeded When it would take the person past 100 MiB.
     */
    public static function check(int $size, int $usage): void
    {
        if ($size > self::MAX_FILE_BYTES) {
            throw FileTooLarge::of($size);
        }

        if ($usage + $size > self::QUOTA_BYTES) {
            throw QuotaExceeded::of($usage, $size);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Files;

use DateTimeImmutable;

/**
 * A file of a person's file manager: its name and type, its size, where it is, and the key its content
 * is stored under (random, never derived from the name).
 */
final readonly class StoredFile
{
    public function __construct(
        public FileId $id,
        public FileEntry $entry,
        public int $size,
        public DateTimeImmutable $createdAt,
    ) {}
}

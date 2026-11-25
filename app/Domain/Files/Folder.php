<?php

declare(strict_types=1);

namespace App\Domain\Files;

use DateTimeImmutable;

/**
 * A folder of a person's file manager; folders nest, and a folder without a parent is in the root.
 */
final readonly class Folder
{
    public function __construct(
        public FolderId $id,
        public ?FolderId $parent,
        public EntryName $name,
        public DateTimeImmutable $createdAt,
    ) {}
}

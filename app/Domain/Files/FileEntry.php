<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * What the index keeps of a file besides its size: the folder it is in (null for the root), its name,
 * its type and the key of its content.
 */
final readonly class FileEntry
{
    public function __construct(
        public ?FolderId $folder,
        public EntryName $name,
        public FileType $type,
        public string $contentKey,
    ) {}
}

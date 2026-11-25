<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * What a folder holds, folders first, each list by name.
 */
final readonly class FolderContents
{
    /**
     * @param  list<Folder>  $folders
     * @param  list<StoredFile>  $files
     */
    public function __construct(
        public array $folders,
        public array $files,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\Folder;
use App\Domain\Files\FolderContents;

/**
 * A folder as the file manager shows it: where it is, what it holds, and how much of the quota is used.
 */
final readonly class FolderView
{
    /**
     * @param  list<Folder>  $path  The folders from the root down to this one; empty for the root.
     */
    public function __construct(
        public array $path,
        public FolderContents $contents,
        public int $usage,
    ) {}
}

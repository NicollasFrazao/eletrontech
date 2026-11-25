<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\Folder;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;

/**
 * One folder of the owner's file manager, or the root.
 */
final readonly class ShowFolder
{
    public function __construct(
        private FileIndex $index,
    ) {}

    /**
     * The folder with its path and contents.
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function handle(AccountId $owner, ?int $folder): FolderView
    {
        $id = $folder === null ? null : new FolderId($folder);

        return new FolderView(
            $id instanceof FolderId ? $this->path($owner, $id) : [],
            $this->index->contents($owner, $id),
            $this->index->usage($owner),
        );
    }

    /**
     * The folders from the root down to this one.
     *
     * @return list<Folder>
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    private function path(AccountId $owner, FolderId $id): array
    {
        $path = [];
        $next = $id;

        while ($next instanceof FolderId) {
            $folder = $this->index->folder($owner, $next);
            array_unshift($path, $folder);
            $next = $folder->parent;
        }

        return $path;
    }
}

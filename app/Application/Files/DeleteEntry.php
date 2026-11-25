<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\FileId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileStore;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;

/**
 * Deletes one of the owner's files, or a folder with everything in it, contents included.
 */
final readonly class DeleteEntry
{
    public function __construct(
        private FileIndex $index,
        private FileStore $store,
    ) {}

    /**
     * Deletes the file and its content.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function file(AccountId $owner, int $id): void
    {
        $this->store->delete($this->index->removeFile($owner, new FileId($id)));
    }

    /**
     * Deletes the folder, its folders and files, and their contents.
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function folder(AccountId $owner, int $id): void
    {
        foreach ($this->index->removeFolder($owner, new FolderId($id)) as $key) {
            $this->store->delete($key);
        }
    }
}

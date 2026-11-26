<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;

/**
 * Creates a folder in another folder of the owner, or in the root.
 */
final readonly class CreateFolder
{
    public function __construct(
        private FileIndex $index,
    ) {}

    /**
     * The new folder.
     *
     * @throws InvalidEntryName When the name cannot be a folder's.
     * @throws FolderNotFound When the parent is not the owner's.
     * @throws NameTaken When the parent already holds something with the name.
     */
    public function handle(AccountId $owner, ?int $parent, string $name): FolderId
    {
        return $this->index->createFolder($owner, $parent === null ? null : new FolderId($parent), EntryName::from($name));
    }
}

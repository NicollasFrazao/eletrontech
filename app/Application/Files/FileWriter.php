<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileEntry;
use App\Domain\Files\FileId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileStore;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use App\Domain\Files\StorageLimit;

/**
 * Stores a file of a known type in a person's manager, within the limits; an upload and an export both
 * end here. The content is stored first and deleted again if the index refuses the file.
 */
final readonly class FileWriter
{
    public function __construct(
        private FileIndex $index,
        private FileStore $store,
    ) {}

    /**
     * Stores the content as a file of the folder (null for the root).
     *
     * @throws FileTooLarge When the content is larger than 10 MiB.
     * @throws QuotaExceeded When it would take the owner past 100 MiB.
     * @throws FolderNotFound When the folder is not the owner's.
     * @throws NameTaken When the folder already holds something with the name.
     */
    public function write(AccountId $owner, ?FolderId $folder, NewFile $file): FileId
    {
        $size = strlen($file->content);
        StorageLimit::check($size, $this->index->usage($owner));
        $key = $this->store->put($file->content);

        try {
            return $this->index->addFile($owner, new FileEntry($folder, $file->name, $file->type, $key), $size);
        } catch (FolderNotFound|NameTaken $refused) {
            $this->store->delete($key);

            throw $refused;
        }
    }

    /**
     * The owner's folder with that name in the root, created when it does not exist yet; null (the root
     * itself) when a file of the root already has the name.
     */
    public function rootFolder(AccountId $owner, EntryName $name): ?FolderId
    {
        try {
            foreach ($this->index->contents($owner, null)->folders as $folder) {
                if (mb_strtolower($folder->name->value) === mb_strtolower($name->value)) {
                    return $folder->id;
                }
            }

            return $this->index->createFolder($owner, null, $name);
        } catch (FolderNotFound|NameTaken) {
            // The root always exists, so only a file of the root with the name lands here.
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\FileId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileStore;
use App\Domain\Files\StoredFile;

/**
 * One of the owner's files: its properties, or its content to download.
 */
final readonly class DownloadFile
{
    public function __construct(
        private FileIndex $index,
        private FileStore $store,
    ) {}

    /**
     * The file's properties: name, type, size, folder and date.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function properties(AccountId $owner, int $id): StoredFile
    {
        return $this->index->file($owner, new FileId($id));
    }

    /**
     * The file's content, with the media type of its kind.
     *
     * @throws FileNotFound When it is not the owner's, or its content is gone.
     */
    public function handle(AccountId $owner, int $id): Download
    {
        $file = $this->properties($owner, $id);

        return new Download($file->entry->name->value, $file->entry->type->mediaType(), $this->store->read($file->entry->contentKey));
    }
}

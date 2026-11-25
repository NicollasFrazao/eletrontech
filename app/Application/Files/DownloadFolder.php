<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\Archiver;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileStore;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;

/**
 * One of the owner's folders, with everything in it, as a zip archive.
 */
final readonly class DownloadFolder
{
    public function __construct(
        private FileIndex $index,
        private FileStore $store,
        private Archiver $archiver,
    ) {}

    /**
     * The archive, named after the folder.
     *
     * @throws FolderNotFound When it is not the owner's.
     * @throws FileNotFound When the content of a file in it is gone.
     */
    public function handle(AccountId $owner, int $id): Download
    {
        $folder = new FolderId($id);
        $contents = [];

        foreach ($this->index->tree($owner, $folder) as $path => $file) {
            $contents[$path] = $this->store->read($file->entry->contentKey);
        }

        return new Download(
            $this->index->folder($owner, $folder)->name->value.'.zip',
            'application/zip',
            $this->archiver->zip($contents),
        );
    }
}

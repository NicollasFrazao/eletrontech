<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\EntryName;
use App\Domain\Files\FileId;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\FileType;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use App\Domain\Files\RefusedFileType;

/**
 * Stores an uploaded file whose name and content are of an accepted type.
 */
final readonly class UploadFile
{
    public function __construct(
        private FileWriter $writer,
    ) {}

    /**
     * Checks the name and the type, then stores the file.
     *
     * @throws InvalidEntryName When the name cannot be a file's.
     * @throws RefusedFileType When the extension is not accepted or the content is of another kind.
     * @throws FileTooLarge When the file is larger than 10 MiB.
     * @throws QuotaExceeded When it would take the owner past 100 MiB.
     * @throws FolderNotFound When the folder is not the owner's.
     * @throws NameTaken When the folder already holds something with the name.
     */
    public function handle(UploadInput $input): FileId
    {
        $name = EntryName::from($input->name);
        $type = FileType::forUpload($name->extension(), $input->content->detectedMime);

        return $this->writer->write(
            $input->owner,
            $input->folder === null ? null : new FolderId($input->folder),
            new NewFile($name, $type, $input->content->bytes),
        );
    }
}

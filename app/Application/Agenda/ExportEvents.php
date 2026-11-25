<?php

declare(strict_types=1);

namespace App\Application\Agenda;

use App\Application\Files\FileWriter;
use App\Application\Files\NewFile;
use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Agenda\EventDocument;
use App\Domain\Agenda\EventRepository;
use App\Domain\Agenda\ExportFormat;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileId;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\FileType;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use LogicException;

/**
 * Writes every event of the owner to a PDF or text document and keeps it in their file manager, in the
 * folder "Events" (as 2015 did).
 */
final readonly class ExportEvents
{
    /**
     * The folder of the root the documents go to.
     */
    public const string FOLDER = 'Events';

    public function __construct(
        private EventRepository $events,
        private EventDocument $document,
        private FileWriter $writer,
        private Clock $clock,
    ) {}

    /**
     * Stores the document and gives the file.
     *
     * @throws FileTooLarge When the document is larger than 10 MiB.
     * @throws QuotaExceeded When it would take the owner past 100 MiB.
     * @throws NameTaken When an export of the same second exists.
     */
    public function handle(AccountId $owner, ExportFormat $format): FileId
    {
        $content = $this->document->render($this->events->all($owner), $format);
        $type = $format === ExportFormat::Pdf ? FileType::Pdf : FileType::Text;

        try {
            $name = EntryName::from('events-'.$this->clock->now()->format('Y-m-d-His').'.'.$type->value);
            $folder = $this->writer->rootFolder($owner, EntryName::from(self::FOLDER));

            return $this->writer->write($owner, $folder, new NewFile($name, $type, $content));
        } catch (InvalidEntryName|FolderNotFound $error) {
            throw new LogicException('The export names its own file in its own folder.', 0, $error);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Agenda\ExportEvents;
use App\Application\Agenda\RecordEvent;
use App\Application\Agenda\ShowAgenda;
use App\Application\Files\CreateFolder;
use App\Application\Files\DeleteEntry;
use App\Application\Files\DownloadFile;
use App\Application\Files\DownloadFolder;
use App\Application\Files\FileWriter;
use App\Application\Files\ShowFolder;
use App\Application\Files\UploadFile;
use App\Domain\Account\Clock;
use App\Domain\Agenda\EventDocument;
use App\Domain\Agenda\EventRepository;
use App\Domain\Files\Archiver;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileStore;
use App\Infrastructure\Agenda\DompdfEventDocument;
use App\Infrastructure\Files\DiskFileStore;
use App\Infrastructure\Files\ZipArchiver;
use App\Infrastructure\Persistence\EloquentEventRepository;
use App\Infrastructure\Persistence\EloquentFileIndex;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the events and the file manager: binds the ports of `App\Domain\Agenda` and
 * `App\Domain\Files` and builds their use cases.
 */
final class FilesServiceProvider extends ServiceProvider
{
    /**
     * Binds the storage of events, folders, files and contents, and the use cases.
     */
    public function register(): void
    {
        $this->app->bind(EventRepository::class, EloquentEventRepository::class);
        $this->app->bind(Archiver::class, ZipArchiver::class);
        $this->app->bind(
            FileIndex::class,
            static fn (Application $app): FileIndex => new EloquentFileIndex($app->make(ConnectionInterface::class)),
        );
        // Resolved when the port is, so a test's Storage::fake() takes the disk's place.
        $this->app->bind(FileStore::class, static fn (): FileStore => new DiskFileStore(Storage::disk('local')));
        $this->app->bind(
            EventDocument::class,
            static fn (Application $app): EventDocument => new DompdfEventDocument($app->make(Factory::class)),
        );

        $this->app->bind(FileWriter::class, static fn (Application $app): FileWriter => new FileWriter(
            $app->make(FileIndex::class),
            $app->make(FileStore::class),
        ));
        $this->app->bind(UploadFile::class, static fn (Application $app): UploadFile => new UploadFile($app->make(FileWriter::class)));
        $this->app->bind(CreateFolder::class, static fn (Application $app): CreateFolder => new CreateFolder($app->make(FileIndex::class)));
        $this->app->bind(ShowFolder::class, static fn (Application $app): ShowFolder => new ShowFolder($app->make(FileIndex::class)));
        $this->app->bind(DownloadFile::class, static fn (Application $app): DownloadFile => new DownloadFile(
            $app->make(FileIndex::class),
            $app->make(FileStore::class),
        ));
        $this->app->bind(DownloadFolder::class, static fn (Application $app): DownloadFolder => new DownloadFolder(
            $app->make(FileIndex::class),
            $app->make(FileStore::class),
            $app->make(Archiver::class),
        ));
        $this->app->bind(DeleteEntry::class, static fn (Application $app): DeleteEntry => new DeleteEntry(
            $app->make(FileIndex::class),
            $app->make(FileStore::class),
        ));

        $this->app->bind(
            RecordEvent::class,
            static fn (Application $app): RecordEvent => new RecordEvent($app->make(EventRepository::class)),
        );
        $this->app->bind(ShowAgenda::class, static fn (Application $app): ShowAgenda => new ShowAgenda($app->make(EventRepository::class)));
        $this->app->bind(ExportEvents::class, static fn (Application $app): ExportEvents => new ExportEvents(
            $app->make(EventRepository::class),
            $app->make(EventDocument::class),
            $app->make(FileWriter::class),
            $app->make(Clock::class),
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileEntry;
use App\Domain\Files\FileId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileType;
use App\Domain\Files\Folder;
use App\Domain\Files\FolderContents;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;
use App\Domain\Files\StoredFile;
use App\Infrastructure\Persistence\Eloquent\FileRecord;
use App\Infrastructure\Persistence\Eloquent\FolderRecord;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;
use ValueError;

/**
 * The folders and files kept in `folders` and `files`; every query is limited to the owner's rows. A
 * name is unique among the folders and files of one folder (the database's collation decides case).
 */
final readonly class EloquentFileIndex implements FileIndex
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /**
     * Writes the folder's row, in one transaction with the checks.
     *
     * @throws FolderNotFound When the parent is not the owner's.
     * @throws NameTaken When the parent already holds something with the name.
     */
    public function createFolder(AccountId $owner, ?FolderId $parent, EntryName $name): FolderId
    {
        return $this->connection->transaction(function () use ($owner, $parent, $name): FolderId {
            $this->claim($owner, $parent, $name);
            $record = FolderRecord::query()->create(['user_id' => $owner->value, 'parent_id' => $parent?->value, 'name' => $name->value]);

            return new FolderId($record->id);
        });
    }

    /**
     * The owner's folder with that id.
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function folder(AccountId $owner, FolderId $id): Folder
    {
        return $this->toFolder($this->folderRecord($owner, $id));
    }

    /**
     * The folders, then the files, of the folder, each by name.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     */
    public function contents(AccountId $owner, ?FolderId $folder): FolderContents
    {
        if ($folder instanceof FolderId) {
            $this->folderRecord($owner, $folder);
        }

        return new FolderContents(
            array_values(
                $this->in(FolderRecord::query(), $owner, 'parent_id', $folder)->orderBy('name')->get()->map($this->toFolder(...))->all(),
            ),
            array_values(
                $this->in(FileRecord::query(), $owner, 'folder_id', $folder)->orderBy('name')->get()->map($this->toFile(...))->all(),
            ),
        );
    }

    /**
     * Writes the file's row, in one transaction with the checks.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     * @throws NameTaken When the folder already holds something with the name.
     */
    public function addFile(AccountId $owner, FileEntry $entry, int $size): FileId
    {
        return $this->connection->transaction(function () use ($owner, $entry, $size): FileId {
            $this->claim($owner, $entry->folder, $entry->name);
            $record = FileRecord::query()->create([
                'user_id' => $owner->value,
                'folder_id' => $entry->folder?->value,
                'name' => $entry->name->value,
                'type' => $entry->type->value,
                'size' => $size,
                'content_key' => $entry->contentKey,
            ]);

            return new FileId($record->id);
        });
    }

    /**
     * The owner's file with that id.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function file(AccountId $owner, FileId $id): StoredFile
    {
        return $this->toFile($this->fileRecord($owner, $id));
    }

    /**
     * Deletes the owner's row and gives its content key.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function removeFile(AccountId $owner, FileId $id): string
    {
        $record = $this->fileRecord($owner, $id);
        $record->delete();

        return $record->content_key;
    }

    /**
     * Deletes the folder (the foreign keys delete what is under it) and gives the content keys of every
     * file that was under it.
     *
     * @return list<string>
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function removeFolder(AccountId $owner, FolderId $id): array
    {
        $keys = array_map(static fn (StoredFile $file): string => $file->entry->contentKey, array_values($this->tree($owner, $id)));
        $this->folderRecord($owner, $id)->delete();

        return $keys;
    }

    /**
     * Every file under the folder, keyed by its path inside it.
     *
     * @return array<string, StoredFile>
     *
     * @throws FolderNotFound When the folder is not the owner's.
     */
    public function tree(AccountId $owner, FolderId $folder): array
    {
        $this->folderRecord($owner, $folder);
        $files = [];
        $pending = [[$folder->value, '']];

        while ($pending !== []) {
            [$id, $prefix] = array_shift($pending);

            foreach ($this->in(FileRecord::query(), $owner, 'folder_id', new FolderId($id))->orderBy('name')->get() as $record) {
                $files[$prefix.$record->name] = $this->toFile($record);
            }

            foreach ($this->in(FolderRecord::query(), $owner, 'parent_id', new FolderId($id))->orderBy('name')->get() as $child) {
                $pending[] = [$child->id, $prefix.$child->name.'/'];
            }
        }

        return $files;
    }

    /**
     * The sum of the sizes of the owner's files.
     */
    public function usage(AccountId $owner): int
    {
        return (int) FileRecord::query()->where('user_id', $owner->value)->sum('size');
    }

    /**
     * Checks that the folder is the owner's and does not hold the name yet, locking its rows.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     * @throws NameTaken When it holds a folder or a file with the name.
     */
    private function claim(AccountId $owner, ?FolderId $folder, EntryName $name): void
    {
        if ($folder instanceof FolderId) {
            FolderRecord::query()->where('user_id', $owner->value)->whereKey($folder->value)->lockForUpdate()->first()
                ?? throw FolderNotFound::withId($folder->value);
        }

        if (
            $this->in(FolderRecord::query(), $owner, 'parent_id', $folder)->where('name', $name->value)->exists()
            || $this->in(FileRecord::query(), $owner, 'folder_id', $folder)->where('name', $name->value)->exists()
        ) {
            throw NameTaken::of($name);
        }
    }

    /**
     * The owner's folder row.
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    private function folderRecord(AccountId $owner, FolderId $id): FolderRecord
    {
        return FolderRecord::query()->where('user_id', $owner->value)->find($id->value) ?? throw FolderNotFound::withId($id->value);
    }

    /**
     * The owner's file row.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    private function fileRecord(AccountId $owner, FileId $id): FileRecord
    {
        return FileRecord::query()->where('user_id', $owner->value)->find($id->value) ?? throw FileNotFound::withId($id->value);
    }

    /**
     * The owner's rows directly in a folder (null for the root).
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     *
     * @return Builder<TModel>
     */
    private function in(Builder $query, AccountId $owner, string $column, ?FolderId $folder): Builder
    {
        $query->where('user_id', $owner->value);

        return $folder instanceof FolderId ? $query->where($column, $folder->value) : $query->whereNull($column);
    }

    /**
     * The folder a row stands for.
     *
     * @throws UnexpectedValueException When the row holds a value no form writes.
     */
    private function toFolder(FolderRecord $record): Folder
    {
        try {
            return new Folder(
                new FolderId($record->id),
                $record->parent_id === null ? null : new FolderId($record->parent_id),
                EntryName::from($record->name),
                $record->created_at->toDateTimeImmutable(),
            );
        } catch (FolderNotFound|InvalidEntryName $error) {
            throw new UnexpectedValueException("The folder {$record->id} holds a value no form writes.", 0, $error);
        }
    }

    /**
     * The file a row stands for.
     *
     * @throws UnexpectedValueException When the row holds a value no upload writes.
     */
    private function toFile(FileRecord $record): StoredFile
    {
        try {
            return new StoredFile(
                new FileId($record->id),
                new FileEntry(
                    $record->folder_id === null ? null : new FolderId($record->folder_id),
                    EntryName::from($record->name),
                    FileType::from($record->type),
                    $record->content_key,
                ),
                $record->size,
                $record->created_at->toDateTimeImmutable(),
            );
        } catch (FileNotFound|FolderNotFound|InvalidEntryName|ValueError $error) {
            throw new UnexpectedValueException("The file {$record->id} holds a value no upload writes.", 0, $error);
        }
    }
}

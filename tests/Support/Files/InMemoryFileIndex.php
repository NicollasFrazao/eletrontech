<?php

declare(strict_types=1);

namespace Tests\Support\Files;

use App\Domain\Account\AccountId;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileEntry;
use App\Domain\Files\FileId;
use App\Domain\Files\FileIndex;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\Folder;
use App\Domain\Files\FolderContents;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\NameTaken;
use App\Domain\Files\StoredFile;
use DateTimeImmutable;

/**
 * Folders and files kept in memory, with their owners; names compared ignoring case, as MySQL does.
 */
final class InMemoryFileIndex implements FileIndex
{
    /**
     * @var array<int, array{int, Folder}> Owner and folder, keyed by id.
     */
    private array $folders = [];

    /**
     * @var array<int, array{int, StoredFile}> Owner and file, keyed by id.
     */
    private array $files = [];

    private int $next = 1;

    public function createFolder(AccountId $owner, ?FolderId $parent, EntryName $name): FolderId
    {
        $this->claim($owner, $parent, $name);
        $id = new FolderId($this->next++);
        $this->folders[$id->value] = [$owner->value, new Folder($id, $parent, $name, new DateTimeImmutable('2026-11-26 10:00'))];

        return $id;
    }

    public function folder(AccountId $owner, FolderId $id): Folder
    {
        [$holder, $folder] = $this->folders[$id->value] ?? [0, null];

        return $holder === $owner->value && $folder !== null ? $folder : throw FolderNotFound::withId($id->value);
    }

    public function contents(AccountId $owner, ?FolderId $folder): FolderContents
    {
        if ($folder instanceof FolderId) {
            $this->folder($owner, $folder);
        }

        return new FolderContents($this->foldersIn($owner, $folder), $this->filesIn($owner, $folder));
    }

    public function addFile(AccountId $owner, FileEntry $entry, int $size): FileId
    {
        $this->claim($owner, $entry->folder, $entry->name);
        $id = new FileId($this->next++);
        $this->files[$id->value] = [$owner->value, new StoredFile($id, $entry, $size, new DateTimeImmutable('2026-11-26 10:00'))];

        return $id;
    }

    public function file(AccountId $owner, FileId $id): StoredFile
    {
        [$holder, $file] = $this->files[$id->value] ?? [0, null];

        return $holder === $owner->value && $file !== null ? $file : throw FileNotFound::withId($id->value);
    }

    public function removeFile(AccountId $owner, FileId $id): string
    {
        $file = $this->file($owner, $id);
        unset($this->files[$id->value]);

        return $file->entry->contentKey;
    }

    public function removeFolder(AccountId $owner, FolderId $id): array
    {
        $keys = [];

        foreach ($this->contents($owner, $id)->folders as $child) {
            $keys = [...$keys, ...$this->removeFolder($owner, $child->id)];
        }

        foreach ($this->filesIn($owner, $id) as $file) {
            $keys[] = $this->removeFile($owner, $file->id);
        }

        unset($this->folders[$id->value]);

        return $keys;
    }

    public function tree(AccountId $owner, FolderId $folder): array
    {
        $files = [];

        foreach ($this->contents($owner, $folder)->files as $file) {
            $files[$file->entry->name->value] = $file;
        }

        foreach ($this->foldersIn($owner, $folder) as $child) {
            foreach ($this->tree($owner, $child->id) as $path => $file) {
                $files[$child->name->value.'/'.$path] = $file;
            }
        }

        return $files;
    }

    public function usage(AccountId $owner): int
    {
        return array_sum(array_map(
            static fn (array $row): int => $row[0] === $owner->value ? $row[1]->size : 0,
            $this->files,
        ));
    }

    /**
     * @throws FolderNotFound
     * @throws NameTaken
     */
    private function claim(AccountId $owner, ?FolderId $folder, EntryName $name): void
    {
        $contents = $this->contents($owner, $folder);
        $names = [
            ...array_map(static fn (Folder $f): string => mb_strtolower($f->name->value), $contents->folders),
            ...array_map(static fn (StoredFile $f): string => mb_strtolower($f->entry->name->value), $contents->files),
        ];

        if (in_array(mb_strtolower($name->value), $names, true)) {
            throw NameTaken::of($name);
        }
    }

    /**
     * @return list<Folder>
     */
    private function foldersIn(AccountId $owner, ?FolderId $parent): array
    {
        $folders = [];

        foreach ($this->folders as [$holder, $folder]) {
            if ($holder === $owner->value && $folder->parent?->value === $parent?->value) {
                $folders[] = $folder;
            }
        }

        usort($folders, static fn (Folder $a, Folder $b): int => strcmp($a->name->value, $b->name->value));

        return $folders;
    }

    /**
     * @return list<StoredFile>
     */
    private function filesIn(AccountId $owner, ?FolderId $folder): array
    {
        $files = [];

        foreach ($this->files as [$holder, $file]) {
            if ($holder === $owner->value && $file->entry->folder?->value === $folder?->value) {
                $files[] = $file;
            }
        }

        usort($files, static fn (StoredFile $a, StoredFile $b): int => strcmp($a->entry->name->value, $b->entry->name->value));

        return $files;
    }
}

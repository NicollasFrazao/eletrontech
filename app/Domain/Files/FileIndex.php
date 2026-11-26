<?php

declare(strict_types=1);

namespace App\Domain\Files;

use App\Domain\Account\AccountId;

/**
 * The folders and files of each person (the 2015 `tb_pasta` and `tb_arquivo`). Every method takes the
 * owner: another person's folder or file is not found, never forbidden, so its existence does not show.
 */
interface FileIndex
{
    /**
     * Creates a folder.
     *
     * @throws FolderNotFound When the parent is not the owner's.
     * @throws NameTaken When the parent already holds a folder or a file with that name.
     */
    public function createFolder(AccountId $owner, ?FolderId $parent, EntryName $name): FolderId;

    /**
     * One of the owner's folders.
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function folder(AccountId $owner, FolderId $id): Folder;

    /**
     * What a folder of the owner holds; null is the root.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     */
    public function contents(AccountId $owner, ?FolderId $folder): FolderContents;

    /**
     * Records a stored file.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     * @throws NameTaken When the folder already holds a folder or a file with that name.
     */
    public function addFile(AccountId $owner, FileEntry $entry, int $size): FileId;

    /**
     * One of the owner's files.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function file(AccountId $owner, FileId $id): StoredFile;

    /**
     * Forgets a file and gives the key of its content, to delete it.
     *
     * @throws FileNotFound When it is not the owner's.
     */
    public function removeFile(AccountId $owner, FileId $id): string;

    /**
     * Forgets a folder with everything in it and gives the keys of the contents, to delete them.
     *
     * @return list<string>
     *
     * @throws FolderNotFound When it is not the owner's.
     */
    public function removeFolder(AccountId $owner, FolderId $id): array;

    /**
     * Every file under a folder of the owner, with its path inside it ("drawings/kitchen.pdf"), to zip it.
     *
     * @return array<string, StoredFile> Keyed by path.
     *
     * @throws FolderNotFound When the folder is not the owner's.
     */
    public function tree(AccountId $owner, FolderId $folder): array;

    /**
     * The bytes the owner's files take.
     */
    public function usage(AccountId $owner): int;
}

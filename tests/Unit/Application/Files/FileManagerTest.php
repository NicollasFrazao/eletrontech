<?php

declare(strict_types=1);

use App\Application\Files\CreateFolder;
use App\Application\Files\DeleteEntry;
use App\Application\Files\Download;
use App\Application\Files\DownloadFile;
use App\Application\Files\DownloadFolder;
use App\Application\Files\FileWriter;
use App\Application\Files\FolderView;
use App\Application\Files\NewFile;
use App\Application\Files\ShowFolder;
use App\Application\Files\UploadedContent;
use App\Application\Files\UploadFile;
use App\Application\Files\UploadInput;
use App\Domain\Account\AccountId;
use App\Domain\Files\EntryName;
use App\Domain\Files\FileId;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileType;
use App\Domain\Files\Folder;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use App\Domain\Files\StorageLimit;
use Tests\Support\Files\InMemoryFileIndex;
use Tests\Support\Files\InMemoryFileStore;
use Tests\Support\Files\JsonArchiver;

/**
 * Uploads a text file for the owner.
 */
function uploadText(InMemoryFileIndex $index, InMemoryFileStore $store, int $owner, ?int $folder, string $name, string $text = 'hello'): int
{
    return new UploadFile(new FileWriter($index, $store))
        ->handle(new UploadInput(new AccountId($owner), $folder, $name, new UploadedContent($text, 'text/plain')))
        ->value;
}

it('stores an upload under a key, never under its name, and gives it back', function (): void {
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;

    $id = uploadText($index, $store, 1, null, 'notes.txt', 'wire gauge');

    $download = new DownloadFile($index, $store)->handle(new AccountId(1), $id);
    expect($download->name)->toBe('notes.txt')
        ->and($download->mediaType)->toBe('text/plain')
        ->and($download->content)->toBe('wire gauge')
        ->and(array_keys($store->contents)[0])->not->toContain('notes');
});

it('finds another person\'s file and folder nowhere', function (): void {
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;
    $file = uploadText($index, $store, 1, null, 'notes.txt');
    $folder = new CreateFolder($index)->handle(new AccountId(1), null, 'Plans')->value;
    $bia = new AccountId(2);

    expect(static fn (): Download => new DownloadFile($index, $store)->handle($bia, $file))->toThrow(FileNotFound::class)
        ->and(static fn () => new DeleteEntry($index, $store)->file($bia, $file))->toThrow(FileNotFound::class)
        ->and(static fn (): FolderView => new ShowFolder($index)->handle($bia, $folder))->toThrow(FolderNotFound::class)
        ->and(static fn (): Download => new DownloadFolder($index, $store, new JsonArchiver)->handle($bia, $folder))
        ->toThrow(FolderNotFound::class)
        ->and(static fn (): int => uploadText($index, $store, 2, $folder, 'x.txt'))->toThrow(FolderNotFound::class)
        ->and($store->contents)->toHaveCount(1);
});

it('deletes the stored content when the index refuses the file', function (): void {
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;
    uploadText($index, $store, 1, null, 'notes.txt');

    expect(static fn (): int => uploadText($index, $store, 1, null, 'NOTES.txt'))->toThrow(NameTaken::class)
        ->and($store->contents)->toHaveCount(1);
});

it('refuses a file that would pass the quota, before storing anything', function (): void {
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;
    $writer = new FileWriter($index, $store);
    $big = str_repeat('a', StorageLimit::MAX_FILE_BYTES);

    for ($i = 0; $i < 10; $i++) {
        $writer->write(new AccountId(1), null, new NewFile(EntryName::from("part{$i}.txt"), FileType::Text, $big));
    }

    $oneMore = new NewFile(EntryName::from('one-more.txt'), FileType::Text, 'a');

    expect(static fn (): FileId => $writer->write(new AccountId(1), null, $oneMore))
        ->toThrow(QuotaExceeded::class)
        ->and($store->contents)->toHaveCount(10);
});

it('shows a folder with its path, and zips and deletes it with everything in it', function (): void {
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;
    $ana = new AccountId(1);
    $plans = new CreateFolder($index)->handle($ana, null, 'Plans')->value;
    $kitchen = new CreateFolder($index)->handle($ana, $plans, 'Kitchen')->value;
    uploadText($index, $store, 1, $plans, 'list.txt', 'list');
    uploadText($index, $store, 1, $kitchen, 'outlets.txt', 'six');
    uploadText($index, $store, 1, null, 'keep.txt', 'keep');

    $view = new ShowFolder($index)->handle($ana, $kitchen);
    $zip = new DownloadFolder($index, $store, new JsonArchiver)->handle($ana, $plans);
    new DeleteEntry($index, $store)->folder($ana, $plans);

    expect(array_map(static fn (Folder $folder): string => $folder->name->value, $view->path))->toBe(['Plans', 'Kitchen'])
        ->and($view->usage)->toBe(11)
        ->and($zip->name)->toBe('Plans.zip')
        ->and(json_decode($zip->content, true))->toBe(['list.txt' => 'list', 'Kitchen/outlets.txt' => 'six'])
        ->and(array_values($store->contents))->toBe(['keep'])
        ->and(new ShowFolder($index)->handle($ana, null)->contents->folders)->toBe([]);
});

it('puts an export in a folder of the root, made once, or in the root when a file has the name', function (): void {
    $index = new InMemoryFileIndex;
    $writer = new FileWriter($index, new InMemoryFileStore);
    $ana = new AccountId(1);
    $bia = new AccountId(2);
    $writer->write($bia, null, new NewFile(EntryName::from('Events'), FileType::Text, 'a file named like the folder'));

    $first = $writer->rootFolder($ana, EntryName::from('Events'));

    expect($writer->rootFolder($ana, EntryName::from('Events')))->toEqual($first)
        ->and($first)->not->toBeNull()
        ->and($writer->rootFolder($bia, EntryName::from('Events')))->toBeNull();
});

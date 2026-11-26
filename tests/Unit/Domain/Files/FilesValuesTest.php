<?php

declare(strict_types=1);

use App\Domain\Files\EntryName;
use App\Domain\Files\FileId;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\FileType;
use App\Domain\Files\FolderId;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\QuotaExceeded;
use App\Domain\Files\RefusedFileType;
use App\Domain\Files\StorageLimit;
use Tests\Support\Files\InMemoryFileStore;

it('reads a name without its outer spaces and gives its extension', function (): void {
    $name = EntryName::from('  Kitchen plan.v2.PDF ');

    expect($name->value)->toBe('Kitchen plan.v2.PDF')
        ->and($name->extension())->toBe('pdf')
        ->and(EntryName::from('Notes')->extension())->toBe('')
        ->and(EntryName::from(str_repeat('a', 100))->value)->toHaveLength(100);
});

it('refuses a name that could be read as a path or holds a control character', function (string $text): void {
    EntryName::from($text);
})->with([
    'empty' => '   ',
    'dot' => '.',
    'dot dot' => '..',
    'slash' => '../../etc/passwd',
    'backslash' => '..\\windows',
    'colon' => 'C:file',
    'wildcards' => 'a*b?.txt',
    'quotes and angles' => '<script>"x".txt',
    'pipe' => 'a|b',
    'newline' => "a\nb.txt",
    'null byte' => "a\0.txt",
    'too long' => str_repeat('a', 101),
])->throws(InvalidEntryName::class);

it('accepts an allowed extension whose content is of its kind', function (string $extension, string $mime, FileType $type): void {
    expect(FileType::forUpload($extension, $mime))->toBe($type);
})->with([
    ['pdf', 'application/pdf', FileType::Pdf],
    ['txt', 'text/plain', FileType::Text],
    ['csv', 'text/plain', FileType::Csv],
    ['jpeg', 'image/jpeg', FileType::Jpeg],
    ['jpg', 'image/jpeg', FileType::Jpeg],
    ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', FileType::WordDocument],
    ['xlsx', 'application/zip', FileType::Spreadsheet],
]);

it('refuses an extension off the list, and content that is not of the extension\'s kind', function (string $extension, string $mime): void {
    FileType::forUpload($extension, $mime);
})->with([
    'php' => ['php', 'text/x-php'],
    'html' => ['html', 'text/html'],
    'svg' => ['svg', 'image/svg+xml'],
    'no extension' => ['', 'text/plain'],
    'php as pdf' => ['pdf', 'text/x-php'],
    'html as txt' => ['txt', 'text/html'],
    'executable as png' => ['png', 'application/x-executable'],
])->throws(RefusedFileType::class);

it('sends each kind with its own media type', function (): void {
    expect(FileType::Pdf->mediaType())->toBe('application/pdf')
        ->and(FileType::Csv->mediaType())->toBe('text/csv')
        ->and(FileType::Spreadsheet->mediaType())->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('keeps a file within 10 MiB and a person within 100 MiB', function (): void {
    StorageLimit::check(StorageLimit::MAX_FILE_BYTES, StorageLimit::QUOTA_BYTES - StorageLimit::MAX_FILE_BYTES);

    expect(static fn () => StorageLimit::check(StorageLimit::MAX_FILE_BYTES + 1, 0))->toThrow(FileTooLarge::class)
        ->and(static fn () => StorageLimit::check(2, StorageLimit::QUOTA_BYTES - 1))->toThrow(QuotaExceeded::class);
});

it('has no file or folder with an id below 1, and no content under an unknown key', function (): void {
    expect(static fn (): FileId => new FileId(0))->toThrow(FileNotFound::class)
        ->and(static fn (): FolderId => new FolderId(0))->toThrow(FolderNotFound::class)
        ->and(static fn (): string => (new InMemoryFileStore)->read('missing'))
        ->toThrow(FileNotFound::class, 'The content of the file is missing.');
});

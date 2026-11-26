<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Application\Files\UploadedContent;
use App\Application\Files\UploadInput;
use App\Domain\Files\StorageLimit;
use App\Http\Controllers\Account\SignedInAccount;
use finfo;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use UnexpectedValueException;

/**
 * The upload form: one file and the folder it goes in (none for the root). The size is checked here too,
 * before the content is read.
 */
final class UploadRequest extends FormRequest
{
    /**
     * Bytes in a kilobyte, the unit of Laravel's `max` rule for files.
     */
    private const int KILOBYTE = 1024;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.intdiv(StorageLimit::MAX_FILE_BYTES, self::KILOBYTE)],
            'folder' => ['nullable', 'integer'],
        ];
    }

    /**
     * The folder, or null for the root.
     */
    public function folder(): ?int
    {
        return $this->filled('folder') ? $this->integer('folder') : null;
    }

    /**
     * The media type of the file's content, read by libmagic from the bytes; never what the browser or
     * the extension claims.
     */
    private function detectedMime(UploadedFile $file): string
    {
        $mime = new finfo(FILEINFO_MIME_TYPE)->file($file->getRealPath());

        return $mime === false ? 'application/octet-stream' : $mime;
    }

    /**
     * The input of the upload use case, with the media type read from the content.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws UnexpectedValueException When the validated file is missing.
     */
    public function toInput(): UploadInput
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw new UnexpectedValueException('The validated upload has no file.');
        }

        return new UploadInput(
            SignedInAccount::id($this),
            $this->folder(),
            $file->getClientOriginalName(),
            new UploadedContent($file->getContent(), $this->detectedMime($file)),
        );
    }
}

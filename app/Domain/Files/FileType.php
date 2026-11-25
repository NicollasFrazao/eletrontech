<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The kinds of file the manager keeps: documents, spreadsheets and pictures an electrician exchanges.
 * An allow-list, checked against both the extension and the content; 2015 only blocked `.php` and
 * `.html` on download.
 */
enum FileType: string
{
    case Pdf = 'pdf';
    case Text = 'txt';
    case Csv = 'csv';
    case Png = 'png';
    case Jpeg = 'jpg';
    case Gif = 'gif';
    case Webp = 'webp';
    case WordDocument = 'docx';
    case Spreadsheet = 'xlsx';
    case OpenDocumentText = 'odt';
    case OpenDocumentSpreadsheet = 'ods';

    /**
     * The type of an upload: its extension must be on the list and its content of that kind.
     *
     * @param  string  $extension  The extension of the name, lower-cased.
     * @param  string  $detectedMime  The media type read from the content.
     *
     * @throws RefusedFileType When the extension is not on the list or the content is of another kind.
     */
    public static function forUpload(string $extension, string $detectedMime): self
    {
        $type = self::tryFrom($extension === 'jpeg' ? 'jpg' : $extension) ?? throw RefusedFileType::extension($extension);

        if (! in_array($detectedMime, $type->contentTypes(), true)) {
            throw RefusedFileType::content($extension, $detectedMime);
        }

        return $type;
    }

    /**
     * The media type a download is sent as.
     */
    public function mediaType(): string
    {
        return $this->contentTypes()[0];
    }

    /**
     * The media types the content of this kind is detected as; the first is the one it is sent as.
     *
     * @return non-empty-list<string>
     */
    private function contentTypes(): array
    {
        return match ($this) {
            self::Pdf => ['application/pdf'],
            self::Text => ['text/plain'],
            self::Csv => ['text/csv', 'text/plain'],
            self::Png => ['image/png'],
            self::Jpeg => ['image/jpeg'],
            self::Gif => ['image/gif'],
            self::Webp => ['image/webp'],
            self::WordDocument => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            self::Spreadsheet => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            self::OpenDocumentText => ['application/vnd.oasis.opendocument.text', 'application/zip'],
            self::OpenDocumentSpreadsheet => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * An upload is not of a kind the manager keeps, or its content is not what its extension says.
 */
final class RefusedFileType extends FilesError
{
    /**
     * The extension is not on the list.
     */
    public static function extension(string $extension): self
    {
        return new self("Files ending in \"{$extension}\" are not accepted.");
    }

    /**
     * The content is of another kind than the extension.
     */
    public static function content(string $extension, string $detected): self
    {
        return new self("A \"{$extension}\" file holds {$detected} content.");
    }
}

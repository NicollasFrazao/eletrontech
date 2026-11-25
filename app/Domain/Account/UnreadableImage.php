<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * An uploaded file is not a picture that can be decoded.
 */
final class UnreadableImage extends AccountError
{
    /**
     * The error for an upload.
     */
    public static function upload(): self
    {
        return new self('The file is not a JPEG, PNG or WebP picture that can be read.');
    }
}

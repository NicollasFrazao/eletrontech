<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Keeps profile pictures, re-encoded and scaled to their kind's size, under names nobody can guess.
 */
interface ImageStore
{
    /**
     * Decodes, crops to the kind's proportions, scales down, re-encodes and stores a picture.
     *
     * @return string The stored path.
     *
     * @throws UnreadableImage When the bytes are not a picture that can be decoded.
     */
    public function store(ImageKind $kind, string $contents): string;

    /**
     * The bytes of a stored picture (JPEG), or null when the file is gone.
     */
    public function read(string $path): ?string;

    /**
     * Deletes a stored picture; a missing file is not an error.
     */
    public function delete(string $path): void;
}

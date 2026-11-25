<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Domain\Account\ImageKind;
use App\Domain\Account\ImageStore;
use App\Domain\Account\UnreadableImage;

/**
 * Pictures in an array; "not an image" is refused, anything else is kept as it is.
 */
final class InMemoryImageStore implements ImageStore
{
    /** @var array<string, string> */
    public array $files = [];

    public function store(ImageKind $kind, string $contents): string
    {
        if ($contents === 'not an image') {
            throw UnreadableImage::upload();
        }

        $path = $kind->value.'-'.(count($this->files) + 1).'.jpg';
        $this->files[$path] = $contents;

        return $path;
    }

    public function read(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }
}

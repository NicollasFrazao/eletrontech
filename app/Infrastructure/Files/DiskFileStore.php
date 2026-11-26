<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileStore;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * File contents on a private disk (the `local` disk, `storage/app/private`), each under a random name in
 * `files/`; the name a person gave the file is never part of the path.
 */
final readonly class DiskFileStore implements FileStore
{
    /**
     * The length of the random part of a key.
     */
    private const int KEY_LENGTH = 40;

    public function __construct(
        private Filesystem $disk,
    ) {}

    /**
     * Writes the content under a new random key.
     */
    public function put(string $content): string
    {
        $key = 'files/'.Str::random(self::KEY_LENGTH);
        $this->disk->put($key, $content);

        return $key;
    }

    /**
     * The content under the key.
     *
     * @throws FileNotFound When nothing is stored under it.
     */
    public function read(string $key): string
    {
        return $this->disk->get($key) ?? throw FileNotFound::content();
    }

    /**
     * Deletes the content under the key.
     */
    public function delete(string $key): void
    {
        $this->disk->delete($key);
    }
}

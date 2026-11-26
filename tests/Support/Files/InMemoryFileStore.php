<?php

declare(strict_types=1);

namespace Tests\Support\Files;

use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileStore;

/**
 * File contents kept in memory under numbered keys.
 */
final class InMemoryFileStore implements FileStore
{
    /**
     * @var array<string, string> Keyed by content key.
     */
    public array $contents = [];

    public function put(string $content): string
    {
        $key = 'key-'.(count($this->contents) + 1).'-'.bin2hex(random_bytes(2));
        $this->contents[$key] = $content;

        return $key;
    }

    public function read(string $key): string
    {
        return $this->contents[$key] ?? throw FileNotFound::content();
    }

    public function delete(string $key): void
    {
        unset($this->contents[$key]);
    }
}

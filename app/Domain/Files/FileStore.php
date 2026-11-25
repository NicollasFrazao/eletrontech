<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * Where the content of the files is kept, under random keys, outside anything the web server serves.
 */
interface FileStore
{
    /**
     * Stores the content and gives its key.
     */
    public function put(string $content): string;

    /**
     * The content under the key.
     *
     * @throws FileNotFound When nothing is stored under it.
     */
    public function read(string $key): string;

    /**
     * Deletes the content under the key; nothing happens when there is none.
     */
    public function delete(string $key): void;
}

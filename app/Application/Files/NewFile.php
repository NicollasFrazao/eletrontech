<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Files\EntryName;
use App\Domain\Files\FileType;

/**
 * A file about to be stored: its name, its type and its content.
 */
final readonly class NewFile
{
    public function __construct(
        public EntryName $name,
        public FileType $type,
        public string $content,
    ) {}
}

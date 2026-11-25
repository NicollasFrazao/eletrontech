<?php

declare(strict_types=1);

namespace App\Application\Files;

/**
 * Something to send to the owner as an attachment: its name, media type and content.
 */
final readonly class Download
{
    public function __construct(
        public string $name,
        public string $mediaType,
        public string $content,
    ) {}
}

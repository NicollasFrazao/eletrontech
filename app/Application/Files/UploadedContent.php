<?php

declare(strict_types=1);

namespace App\Application\Files;

/**
 * The bytes of an upload and the media type the edge detected in them.
 */
final readonly class UploadedContent
{
    public function __construct(
        public string $bytes,
        public string $detectedMime,
    ) {}
}

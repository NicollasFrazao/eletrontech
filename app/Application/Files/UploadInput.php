<?php

declare(strict_types=1);

namespace App\Application\Files;

use App\Domain\Account\AccountId;

/**
 * An upload as the form sends it: the folder (null for the root), the file's name, its content and the
 * media type read from the content.
 */
final readonly class UploadInput
{
    public function __construct(
        public AccountId $owner,
        public ?int $folder,
        public string $name,
        public UploadedContent $content,
    ) {}
}

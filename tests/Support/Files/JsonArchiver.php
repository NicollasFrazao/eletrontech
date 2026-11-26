<?php

declare(strict_types=1);

namespace Tests\Support\Files;

use App\Domain\Files\Archiver;

/**
 * An "archive" that is the JSON of its contents, so a unit test reads what went in.
 */
final class JsonArchiver implements Archiver
{
    public function zip(array $contents): string
    {
        return json_encode($contents, JSON_THROW_ON_ERROR);
    }
}

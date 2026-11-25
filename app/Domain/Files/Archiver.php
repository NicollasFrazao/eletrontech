<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * Packs files into one archive, to download a folder (2015 offered folders as zip).
 */
interface Archiver
{
    /**
     * A zip archive with each content under its path.
     *
     * @param  array<string, string>  $contents  Keyed by path inside the archive.
     */
    public function zip(array $contents): string;
}

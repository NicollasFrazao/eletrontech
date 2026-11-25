<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use App\Domain\Files\Archiver;
use RuntimeException;
use ZipArchive;

/**
 * Zip archives made with PHP's zip extension, in a temporary file that is deleted afterwards.
 */
final readonly class ZipArchiver implements Archiver
{
    /**
     * The archive's bytes; an empty folder gives an empty archive.
     *
     * @param  array<string, string>  $contents
     *
     * @throws RuntimeException When the temporary archive cannot be written.
     */
    public function zip(array $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open a temporary zip archive.');
        }

        foreach ($contents as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes === false ? throw new RuntimeException('Could not read the zip archive.') : $bytes;
    }
}

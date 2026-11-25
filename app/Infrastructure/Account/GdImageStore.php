<?php

declare(strict_types=1);

namespace App\Infrastructure\Account;

use App\Domain\Account\ImageKind;
use App\Domain\Account\ImageStore;
use App\Domain\Account\UnreadableImage;
use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Profile pictures decoded and re-encoded with GD, kept on a private disk (never under `public/`).
 *
 * Re-encoding drops whatever the upload carried besides pixels (EXIF, comments, a script after the
 * image data), and the random name tells nothing about the person.
 */
final readonly class GdImageStore implements ImageStore
{
    /**
     * The folder of the disk the pictures are kept in.
     */
    private const string FOLDER = 'profile-images';

    /**
     * JPEG quality of the stored pictures.
     */
    private const int QUALITY = 85;

    /**
     * The most pixels a picture may have before it is decoded, so a small file that inflates to a
     * huge bitmap is refused before it fills the memory.
     */
    private const int MAX_PIXELS = 40_000_000;

    /**
     * The formats accepted, by the type getimagesize reports.
     */
    private const array FORMATS = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public function __construct(
        private Filesystem $disk,
    ) {}

    /**
     * Decodes the upload, cuts and scales it to the kind, re-encodes it as JPEG and stores it under a random name.
     */
    public function store(ImageKind $kind, string $contents): string
    {
        $source = $this->decode($contents);
        $picture = $this->fit($source, $kind);

        ob_start();
        imagejpeg($picture, null, self::QUALITY);
        $jpeg = (string) ob_get_clean();

        $path = self::FOLDER.'/'.Str::random(40).'.jpg';
        $this->disk->put($path, $jpeg);

        return $path;
    }

    /**
     * The bytes of a stored picture, or null when the file is gone.
     */
    public function read(string $path): ?string
    {
        return $this->disk->get($path);
    }

    /**
     * Deletes a stored picture; a missing file is not an error.
     */
    public function delete(string $path): void
    {
        $this->disk->delete($path);
    }

    /**
     * The bitmap of an accepted picture.
     *
     * @throws UnreadableImage When the bytes are not a JPEG, PNG or WebP, are too large, or do not decode.
     */
    private function decode(string $contents): GdImage
    {
        $info = @getimagesizefromstring($contents);

        if (
            $info === false
            || ! in_array($info[2], self::FORMATS, true)
            || $info[0] * $info[1] > self::MAX_PIXELS
        ) {
            throw UnreadableImage::upload();
        }

        $image = @imagecreatefromstring($contents);

        return $image === false ? throw UnreadableImage::upload() : $image;
    }

    /**
     * The centre of the picture cut to the kind's proportions, scaled down to fit its size (never up).
     */
    private function fit(GdImage $source, ImageKind $kind): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = $kind->maxWidth() / $kind->maxHeight();

        $cropWidth = min($width, (int) round($height * $ratio));
        $cropHeight = min($height, (int) round($width / $ratio));
        $scale = min(1, $kind->maxWidth() / $cropWidth);
        $targetWidth = max(1, (int) round($cropWidth * $scale));
        $targetHeight = max(1, (int) round($cropHeight * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            intdiv($width - $cropWidth, 2),
            intdiv($height - $cropHeight, 2),
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight,
        );

        return $target;
    }
}

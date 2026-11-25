<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * The two pictures of a profile, each with the largest size it is kept at.
 */
enum ImageKind: string
{
    case Photo = 'photo';
    case Cover = 'cover';

    /**
     * The widest the picture is kept, in pixels.
     */
    public function maxWidth(): int
    {
        return match ($this) {
            self::Photo => 512,
            self::Cover => 1500,
        };
    }

    /**
     * The tallest the picture is kept, in pixels.
     */
    public function maxHeight(): int
    {
        return match ($this) {
            self::Photo => 512,
            self::Cover => 500,
        };
    }
}

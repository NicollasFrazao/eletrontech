<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * How the account shows itself and its interface: the preferences and the pictures.
 */
final readonly class Profile
{
    /**
     * @param  array<value-of<ImageKind>, string>  $images  The stored path of each picture the account has.
     */
    public function __construct(
        public Preferences $preferences,
        public array $images,
    ) {}

    /**
     * The stored path of the picture, or null when there is none.
     */
    public function imagePath(ImageKind $kind): ?string
    {
        return $this->images[$kind->value] ?? null;
    }

    /**
     * The same profile with other preferences.
     */
    public function withPreferences(Preferences $preferences): self
    {
        return new self($preferences, $this->images);
    }

    /**
     * The same profile with a new picture of that kind.
     */
    public function withImage(ImageKind $kind, string $path): self
    {
        return new self($this->preferences, [...$this->images, $kind->value => $path]);
    }
}

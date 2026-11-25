<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A person's name: trimmed, single-spaced, between 2 and 100 characters.
 */
final readonly class PersonName
{
    /**
     * Shorter than this is an initial, not a name.
     */
    private const int MIN_LENGTH = 2;

    /**
     * The column size of the 2015 `nm_usuario`.
     */
    private const int MAX_LENGTH = 100;

    private function __construct(public string $value) {}

    /**
     * Reads a name as a person typed it.
     *
     * @throws InvalidAccountData When it is shorter than 2 or longer than 100 characters.
     */
    public static function from(string $text): self
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $text));
        $length = mb_strlen($name);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidAccountData::of(AccountField::Name, 'between 2 and 100 characters');
        }

        return new self($name);
    }
}

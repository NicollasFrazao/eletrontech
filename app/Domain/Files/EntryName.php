<?php

declare(strict_types=1);

namespace App\Domain\Files;

/**
 * The name of a folder or a file as its owner sees it. It is only a label: what is stored on disk has a
 * random name, so no name can reach another path. Still, a name holds no separator or control
 * character and is not "." or "..", so it reads the same everywhere it is shown or zipped.
 */
final readonly class EntryName
{
    /**
     * The longest name, in characters (the 2015 `nm_pasta` and `nm_arquivo`).
     */
    public const int MAX_LENGTH = 100;

    private function __construct(public string $value) {}

    /**
     * Reads a name as typed, without its outer spaces.
     *
     * @throws InvalidEntryName When it is empty, too long, "." or "..", or holds / \ : * ? " < > | or a control character.
     */
    public static function from(string $text): self
    {
        $name = trim($text);

        if (
            in_array($name, ['', '.', '..'], true)
            || mb_strlen($name) > self::MAX_LENGTH
            || preg_match('/[\/\\\\:*?"<>|\p{Cc}]/u', $name) !== 0
        ) {
            throw InvalidEntryName::of($text);
        }

        return new self($name);
    }

    /**
     * The extension after the last dot, lower-cased; empty when there is none.
     */
    public function extension(): string
    {
        $dot = strrpos($this->value, '.');

        return $dot === false ? '' : mb_strtolower(substr($this->value, $dot + 1));
    }
}

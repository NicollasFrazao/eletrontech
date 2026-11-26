<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * The identifier of a tool, the same as its calculator's ("ohms-law") or the editor's.
 */
final readonly class ToolId
{
    /**
     * The longest identifier the tools table holds.
     */
    private const int MAX_LENGTH = 50;

    private function __construct(public string $value) {}

    /**
     * Reads an identifier: lower-case words of letters and digits joined by hyphens.
     *
     * @throws UnknownTool When the text cannot be a tool identifier.
     */
    public static function from(string $text): self
    {
        if (
            strlen($text) > self::MAX_LENGTH
            || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $text) !== 1
        ) {
            throw UnknownTool::withId($text);
        }

        return new self($text);
    }

    /**
     * The everyday tools every package carries (`Package::ESSENTIALS`), whose identifiers are known good.
     *
     * @return non-empty-list<self>
     */
    public static function essentials(): array
    {
        return array_map(static fn (string $id): self => new self($id), Package::ESSENTIALS);
    }
}

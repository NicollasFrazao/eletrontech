<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * The name of a tool or a package as the catalogue shows it: from 1 to 100 characters, without the
 * spaces around it.
 */
final readonly class CatalogueName
{
    /**
     * The longest name the tools and packages tables hold.
     */
    public const int MAX_LENGTH = 100;

    public string $value;

    /**
     * @throws InvalidCatalogueName When the name is blank or longer than 100 characters.
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '' || mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw InvalidCatalogueName::of($trimmed);
        }

        $this->value = $trimmed;
    }
}

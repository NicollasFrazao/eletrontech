<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use Brick\Math\BigDecimal;

/**
 * What a package costs, in reais with cents: from 0.00 (free of charge, as everything was in 2015) to
 * 99,999.99.
 */
final readonly class Price
{
    /**
     * The cents of a price, two decimal places.
     */
    private const int SCALE = 2;

    /**
     * A non-negative amount with at most five whole digits and two decimal places, written with a dot.
     */
    private const string PATTERN = '/^\d{1,5}(?:\.\d{1,2})?$/';

    private function __construct(public BigDecimal $amount) {}

    /**
     * Reads a price as the panel's form sends it ("49.9", "120").
     *
     * @throws InvalidPrice When the text is not an amount from 0.00 to 99,999.99 with at most two decimal places.
     */
    public static function of(string $text): self
    {
        $trimmed = trim($text);

        if (preg_match(self::PATTERN, $trimmed) !== 1) {
            throw InvalidPrice::of($trimmed);
        }

        return new self(BigDecimal::of($trimmed)->toScale(self::SCALE));
    }
}

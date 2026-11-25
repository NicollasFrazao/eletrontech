<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A CPF, the Brazilian taxpayer number: eleven digits whose last two are check digits of the first nine.
 *
 * Kept as the digits only, so "123.456.789-09" and "12345678909" are the same CPF.
 */
final readonly class Cpf
{
    /**
     * The digits of a CPF, the two check digits included.
     */
    private const int LENGTH = 11;

    /**
     * The modulus of the check-digit algorithm.
     */
    private const int MODULUS = 11;

    /**
     * A remainder of ten is written as the digit zero.
     */
    private const int TEN = 10;

    /**
     * @param  string  $digits  Eleven digits.
     */
    private function __construct(public string $digits) {}

    /**
     * Reads a CPF with or without its dots and dash.
     *
     * @throws InvalidAccountData When it is not eleven digits, all the same digit, or the check digits do not match.
     */
    public static function from(string $text): self
    {
        $digits = (string) preg_replace('/[.\-\s]/', '', $text);

        if (
            preg_match('/^\d{11}$/', $digits) !== 1
            || str_repeat($digits[0], self::LENGTH) === $digits
            || self::checkDigit(substr($digits, 0, -2)) !== (int) $digits[self::LENGTH - 2]
            || self::checkDigit(substr($digits, 0, -1)) !== (int) $digits[self::LENGTH - 1]
        ) {
            throw InvalidAccountData::of(AccountField::Cpf, 'not a valid CPF');
        }

        return new self($digits);
    }

    /**
     * The check digit of the leading digits: each weighted from its count plus one down to 2.
     */
    private static function checkDigit(string $leading): int
    {
        $weight = strlen($leading) + 1;
        $sum = 0;

        foreach (str_split($leading) as $digit) {
            $sum += (int) $digit * $weight;
            $weight--;
        }

        $remainder = $sum * self::TEN % self::MODULUS;

        return $remainder === self::TEN ? 0 : $remainder;
    }
}

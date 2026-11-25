<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * A Brazilian phone number: a two-digit area code and eight digits for a landline or nine, starting
 * with 9, for a mobile. Kept as the digits only.
 */
final readonly class PhoneNumber
{
    /**
     * @param  string  $digits  The area code and the number, 10 or 11 digits.
     */
    private function __construct(public string $digits) {}

    /**
     * Reads a number with or without its brackets, spaces and dash.
     *
     * @throws InvalidAccountData When it is not an area code (11 to 99, no zero) followed by a landline or mobile number.
     */
    public static function from(string $text): self
    {
        $digits = (string) preg_replace('/[()\-\s]/', '', $text);

        if (preg_match('/^[1-9]{2}(?:[2-5]\d{7}|9\d{8})$/', $digits) !== 1) {
            throw InvalidAccountData::of(AccountField::Phone, 'not a phone number with its area code');
        }

        return new self($digits);
    }

    /**
     * Whether it is a mobile number (nine digits after the area code).
     */
    public function isMobile(): bool
    {
        return preg_match('/^\d{2}9\d{8}$/', $this->digits) === 1;
    }
}

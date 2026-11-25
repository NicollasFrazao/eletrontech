<?php

declare(strict_types=1);

namespace App\Application\Account;

/**
 * The details a person may change on the profile, as typed.
 */
final readonly class ProfileDetailsInput
{
    /**
     * @param  string  $phoneType  A PhoneType value: home, mobile or business.
     */
    public function __construct(
        public string $name,
        public string $phoneNumber,
        public string $phoneType,
    ) {}
}

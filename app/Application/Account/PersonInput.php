<?php

declare(strict_types=1);

namespace App\Application\Account;

/**
 * The person of a sign-up, as typed.
 */
final readonly class PersonInput
{
    /**
     * @param  string  $phoneType  A PhoneType value: home, mobile or business.
     */
    public function __construct(
        public string $name,
        public string $cpf,
        public string $phoneNumber,
        public string $phoneType,
    ) {}
}

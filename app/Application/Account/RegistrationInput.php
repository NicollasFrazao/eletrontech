<?php

declare(strict_types=1);

namespace App\Application\Account;

use SensitiveParameter;

/**
 * A sign-up, as typed: the person, the email and the password.
 */
final readonly class RegistrationInput
{
    public function __construct(
        public PersonInput $person,
        public string $email,
        #[SensitiveParameter] public string $password,
    ) {}
}

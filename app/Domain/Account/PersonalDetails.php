<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Who the account belongs to: the name and the phone a person may change, and the CPF, which is fixed.
 *
 * The 2015 sign-up also asked for sex and birth date; no feature uses them, so v2 does not collect them.
 */
final readonly class PersonalDetails
{
    public function __construct(
        public PersonName $name,
        public Cpf $cpf,
        public ContactPhone $phone,
    ) {}

    /**
     * The same person with a new name and phone; the CPF does not change.
     */
    public function withNameAndPhone(PersonName $name, ContactPhone $phone): self
    {
        return new self($name, $this->cpf, $phone);
    }
}

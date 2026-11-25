<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * The phone an account is reached on, with what kind of phone it is.
 */
final readonly class ContactPhone
{
    /**
     * @throws InvalidAccountData When the type is mobile and the number is a landline.
     */
    public function __construct(
        public PhoneNumber $number,
        public PhoneType $type,
    ) {
        if ($type === PhoneType::Mobile && ! $number->isMobile()) {
            throw InvalidAccountData::of(AccountField::Phone, 'a mobile phone has nine digits after the area code');
        }
    }
}

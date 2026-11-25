<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountField;
use App\Domain\Account\ContactPhone;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PhoneNumber;
use App\Domain\Account\PhoneType;

/**
 * Reads a typed phone number and type into a ContactPhone.
 */
final class ContactPhones
{
    /**
     * The phone, checked.
     *
     * @throws InvalidAccountData When the number is not a phone number, the type is unknown, or a mobile type has a landline.
     */
    public static function from(string $number, string $type): ContactPhone
    {
        $phoneType = PhoneType::tryFrom($type)
            ?? throw InvalidAccountData::of(AccountField::Phone, 'an unknown kind of phone');

        return new ContactPhone(PhoneNumber::from($number), $phoneType);
    }
}

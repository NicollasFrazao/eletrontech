<?php

declare(strict_types=1);

use App\Domain\Account\AccountField;
use App\Domain\Account\AccountId;
use App\Domain\Account\ContactPhone;
use App\Domain\Account\Cpf;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PersonName;
use App\Domain\Account\PhoneNumber;
use App\Domain\Account\PhoneType;
use App\Domain\Account\PlainPassword;

/**
 * Asserts that reading a value fails on one field.
 */
function expectRefused(Closure $read, AccountField $field): void
{
    expect($read)->toThrow(function (InvalidAccountData $error) use ($field): void {
        expect($error->field)->toBe($field);
    });
}

it('keeps the digits of a valid CPF, with or without its punctuation', function (string $typed, string $digits): void {
    expect(Cpf::from($typed)->digits)->toBe($digits);
})->with([
    'punctuated' => ['529.982.247-25', '52998224725'],
    'digits only' => ['12345678909', '12345678909'],
    'a zero check digit' => ['987.654.321-00', '98765432100'],
    'spaces' => [' 111 444 777 35 ', '11144477735'],
]);

it('refuses a CPF with a wrong check digit, the wrong length, letters or one repeated digit', function (string $typed): void {
    expectRefused(fn (): Cpf => Cpf::from($typed), AccountField::Cpf);
})->with(['529.982.247-24', '529.982.247-35', '5299822472', '529982247250', '529.982.247-2a', '111.111.111-11', '000.000.000-00', '']);

it('lower-cases and trims an email address', function (): void {
    expect(EmailAddress::from('  Ana.Souza@Example.COM ')->value)->toBe('ana.souza@example.com');
});

it('refuses a text that is not an email address, or one longer than 254 characters', function (string $typed): void {
    expectRefused(fn (): EmailAddress => EmailAddress::from($typed), AccountField::Email);
})->with(['ana', 'ana@', '@example.com', 'ana souza@example.com', str_repeat('a', 245).'@example.com']);

it('single-spaces and trims a name of 2 to 100 characters', function (): void {
    expect(PersonName::from("  Ana \t Maria  Souza ")->value)->toBe('Ana Maria Souza')
        ->and(PersonName::from('Al')->value)->toBe('Al')
        ->and(PersonName::from(str_repeat('é', 100))->value)->toBe(str_repeat('é', 100));
});

it('refuses a name shorter than 2 or longer than 100 characters', function (string $typed): void {
    expectRefused(fn (): PersonName => PersonName::from($typed), AccountField::Name);
})->with(['', ' A ', str_repeat('a', 101)]);

it('reads a landline and a mobile with their area code', function (string $typed, string $digits, bool $isMobile): void {
    $phone = PhoneNumber::from($typed);

    expect([$phone->digits, $phone->isMobile()])->toBe([$digits, $isMobile]);
})->with([
    'landline' => ['(13) 3222-1234', '1332221234', false],
    'mobile' => ['(13) 99876-5432', '13998765432', true],
]);

it('refuses a phone without an area code, with a zero in it, or of the wrong shape', function (string $typed): void {
    expectRefused(fn (): PhoneNumber => PhoneNumber::from($typed), AccountField::Phone);
})->with(['3222-1234', '(03) 3222-1234', '(10) 3222-1234', '(13) 1222-1234', '(13) 89876-5432', '(13) 99876-543']);

it('refuses a mobile phone type with a landline number, and takes any other pairing', function (): void {
    $landline = PhoneNumber::from('1332221234');
    $mobile = PhoneNumber::from('13998765432');

    expectRefused(fn (): ContactPhone => new ContactPhone($landline, PhoneType::Mobile), AccountField::Phone);
    expect(new ContactPhone($landline, PhoneType::Business)->type)->toBe(PhoneType::Business)
        ->and(new ContactPhone($mobile, PhoneType::Home)->type)->toBe(PhoneType::Home);
});

it('takes a password of 8 characters up to 72 bytes, counting bytes for the upper limit', function (): void {
    expect(PlainPassword::from('12345678')->value)->toBe('12345678')
        ->and(PlainPassword::from(str_repeat('a', 72))->value)->toHaveLength(72);

    expectRefused(fn (): PlainPassword => PlainPassword::from('1234567'), AccountField::Password);
    expectRefused(fn (): PlainPassword => PlainPassword::from(str_repeat('a', 73)), AccountField::Password);
    expectRefused(fn (): PlainPassword => PlainPassword::from(str_repeat('é', 37)), AccountField::Password);
});

it('refuses an account id that is not positive', function (): void {
    expect(fn (): AccountId => new AccountId(0))->toThrow(InvalidArgumentException::class);
});

<?php

declare(strict_types=1);

use App\Domain\Account\Access;
use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\ActivityType;
use App\Domain\Account\ContactPhone;
use App\Domain\Account\Cpf;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\HashedPassword;
use App\Domain\Account\ImageKind;
use App\Domain\Account\PersonalDetails;
use App\Domain\Account\PersonName;
use App\Domain\Account\PhoneNumber;
use App\Domain\Account\PhoneType;
use App\Domain\Account\Preferences;
use App\Domain\Account\Profile;
use App\Domain\Account\SignInEffect;

function anAccount(): Account
{
    return new Account(
        new AccountId(7),
        new PersonalDetails(
            PersonName::from('Ana Souza'),
            Cpf::from('52998224725'),
            new ContactPhone(PhoneNumber::from('13998765432'), PhoneType::Mobile),
        ),
        Access::forNewAccount(EmailAddress::from('ana@example.com'), new HashedPassword('h1')),
        new Profile(new Preferences(false, true), []),
    );
}

it('keeps the first confirmation date of the email', function (): void {
    $account = anAccount();

    $account->verifyEmail(new DateTimeImmutable('2026-11-25 10:00'));
    $account->verifyEmail(new DateTimeImmutable('2026-11-26 10:00'));

    expect($account->access()->emailVerifiedAt)->toEqual(new DateTimeImmutable('2026-11-25 10:00'));
});

it('changes the name and the phone but never the CPF', function (): void {
    $account = anAccount();

    $account->updateDetails(PersonName::from('Ana Lima'), new ContactPhone(PhoneNumber::from('1332221234'), PhoneType::Home));

    expect($account->details()->name->value)->toBe('Ana Lima')
        ->and($account->details()->phone->number->digits)->toBe('1332221234')
        ->and($account->details()->cpf->digits)->toBe('52998224725');
});

it('replaces the password hash and the profile', function (): void {
    $account = anAccount();

    $account->changePassword(new HashedPassword('h2'));
    $account->replaceProfile($account->profile()->withPreferences(new Preferences(true, false))->withImage(ImageKind::Photo, 'p.jpg'));

    expect($account->access()->password->value)->toBe('h2')
        ->and($account->profile()->preferences)->toEqual(new Preferences(true, false))
        ->and($account->profile()->imagePath(ImageKind::Photo))->toBe('p.jpg')
        ->and($account->profile()->imagePath(ImageKind::Cover))->toBeNull();
});

it('deactivates for 30 days and comes back when signed in before the deadline', function (): void {
    $account = anAccount();
    $account->deactivate(new DateTimeImmutable('2026-11-01 12:00'));

    expect($account->access()->reactivationDeadline)->toEqual(new DateTimeImmutable('2026-12-01 12:00'))
        ->and($account->hasExpired(new DateTimeImmutable('2026-12-01 12:00')))->toBeFalse()
        ->and($account->signIn(new DateTimeImmutable('2026-12-01 12:00')))->toBe(SignInEffect::Reactivated)
        ->and($account->access()->reactivationDeadline)->toBeNull()
        ->and($account->signIn(new DateTimeImmutable('2026-12-02 12:00')))->toBe(SignInEffect::None);
});

it('expires a deactivated account signed in to after its deadline, and keeps it deactivated', function (): void {
    $account = anAccount();
    $account->deactivate(new DateTimeImmutable('2026-11-01 12:00'));
    $after = new DateTimeImmutable('2026-12-01 12:00:01');

    expect($account->hasExpired($after))->toBeTrue()
        ->and($account->signIn($after))->toBe(SignInEffect::Expired)
        ->and($account->access()->reactivationDeadline)->not->toBeNull()
        ->and(anAccount()->hasExpired($after))->toBeFalse();
});

it('is active until deactivated, and active again when a sign-in reactivates it', function (): void {
    $account = anAccount();

    expect($account->isActive())->toBeTrue();

    $account->deactivate(new DateTimeImmutable('2026-11-01 12:00'));

    expect($account->isActive())->toBeFalse();

    $account->signIn(new DateTimeImmutable('2026-11-02 12:00'));

    expect($account->isActive())->toBeTrue();
});

it('names the picture replaced as an activity of its own kind', function (): void {
    expect(ActivityType::imageReplaced(ImageKind::Photo))->toBe(ActivityType::PhotoReplaced)
        ->and(ActivityType::imageReplaced(ImageKind::Cover))->toBe(ActivityType::CoverReplaced)
        ->and([ImageKind::Photo->maxWidth(), ImageKind::Photo->maxHeight()])->toBe([512, 512])
        ->and([ImageKind::Cover->maxWidth(), ImageKind::Cover->maxHeight()])->toBe([1500, 500]);
});

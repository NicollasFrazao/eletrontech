<?php

declare(strict_types=1);

use App\Application\Account\DeactivateAccount;
use App\Application\Account\PersonInput;
use App\Application\Account\RegistrationInput;
use App\Application\Account\SignIn;
use App\Application\Account\SignOut;
use App\Domain\Account\AccountField;
use App\Domain\Account\AccountId;
use App\Domain\Account\ActivityType;
use App\Domain\Account\AlreadyRegistered;
use App\Domain\Account\ImageKind;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\InvalidCredentials;
use Tests\Support\Account\AccountKit;

function signIn(AccountKit $kit): SignIn
{
    return new SignIn($kit->accounts, $kit->hasher, $kit->recorder(), $kit->closer());
}

function deactivate(AccountKit $kit, AccountId $id): void
{
    new DeactivateAccount($kit->accounts, $kit->hasher, $kit->activity, $kit->clock)->handle($id, 'correct horse');
}

it('registers an account with its values cleaned, a hashed password, and records it', function (): void {
    $kit = new AccountKit;

    $id = $kit->registered();
    $account = $kit->accounts->get($id);

    expect($account->details()->name->value)->toBe('Ana Souza')
        ->and($account->details()->cpf->digits)->toBe('52998224725')
        ->and($account->access()->password->value)->toBe('hashed:correct horse')
        ->and($account->access()->emailVerifiedAt)->toBeNull()
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered]);
});

it('refuses an unknown kind of phone and a short password', function (): void {
    $kit = new AccountKit;
    $input = AccountKit::input(password: 'short');

    expect(fn (): AccountId => $kit->register()->handle($input))
        ->toThrow(function (InvalidAccountData $error): void {
            expect($error->field)->toBe(AccountField::Password);
        });
    expect(fn (): AccountId => $kit->register()->handle(new RegistrationInput(
        new PersonInput('Ana', '52998224725', '13998765432', 'fax'),
        'ana@example.com',
        'correct horse',
    )))->toThrow(function (InvalidAccountData $error): void {
        expect($error->field)->toBe(AccountField::Phone);
    });
});

it('refuses an email or a CPF an open account holds, even a deactivated one before its deadline', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    deactivate($kit, $id);

    expect(fn (): AccountId => $kit->registered(cpf: '123.456.789-09'))
        ->toThrow(function (AlreadyRegistered $error): void {
            expect($error->field)->toBe(AccountField::Email);
        })
        ->and(fn (): AccountId => $kit->registered(email: 'bia@example.com'))
        ->toThrow(function (AlreadyRegistered $error): void {
            expect($error->field)->toBe(AccountField::Cpf);
        });
});

it('closes an account past its deadline that holds the email of a sign-up, and registers the new one', function (): void {
    $kit = new AccountKit;
    $old = $kit->registered();
    $kit->accounts->save(tap($kit->accounts->get($old), function ($account) use ($kit): void {
        $account->replaceProfile($account->profile()->withImage(ImageKind::Photo, $kit->images->store(ImageKind::Photo, 'jpeg')));
    }));
    deactivate($kit, $old);
    $kit->clock->advance('+31 days');

    $new = $kit->registered(cpf: '123.456.789-09');

    expect($kit->accounts->closed)->toBe([$old->value])
        ->and($kit->images->files)->toBe([])
        ->and($kit->activity->typesOf($old))->toContain(ActivityType::Closed)
        ->and($new)->not->toEqual($old);
});

it('signs in with the right password and records it', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    expect(signIn($kit)->handle(' ANA@example.com', 'correct horse'))->toEqual($id)
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::SignedIn]);
});

it('answers a wrong password, an unknown email and a non-email with one error', function (string $email, string $password): void {
    $kit = new AccountKit;
    $kit->registered();

    expect(fn (): AccountId => signIn($kit)->handle($email, $password))->toThrow(InvalidCredentials::class);
})->with([
    'wrong password' => ['ana@example.com', 'wrong horse'],
    'unknown email' => ['bia@example.com', 'correct horse'],
    'not an email' => ['ana', 'correct horse'],
]);

it('spends a decoy check on an unknown email, so it takes as long as a wrong password', function (): void {
    $kit = new AccountKit;

    expect(fn (): AccountId => signIn($kit)->handle('bia@example.com', 'x'))->toThrow(InvalidCredentials::class)
        ->and($kit->hasher->decoyChecks)->toBe(1);
});

it('reactivates a deactivated account signed in to before its deadline', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    deactivate($kit, $id);
    $kit->clock->advance('+30 days');

    signIn($kit)->handle('ana@example.com', 'correct horse');

    expect($kit->accounts->get($id)->access()->reactivationDeadline)->toBeNull()
        ->and($kit->activity->typesOf($id))->toBe([
            ActivityType::Registered, ActivityType::Deactivated, ActivityType::Reactivated, ActivityType::SignedIn,
        ]);
});

it('closes a deactivated account signed in to after its deadline, and refuses the sign-in', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    deactivate($kit, $id);
    $kit->clock->advance('+30 days +1 second');

    expect(fn (): AccountId => signIn($kit)->handle('ana@example.com', 'correct horse'))
        ->toThrow(InvalidCredentials::class)
        ->and($kit->accounts->closed)->toBe([$id->value])
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::Deactivated, ActivityType::Closed]);
});

it('records a sign-out', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    new SignOut($kit->activity, $kit->clock)->handle($id);

    expect($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::SignedOut]);
});

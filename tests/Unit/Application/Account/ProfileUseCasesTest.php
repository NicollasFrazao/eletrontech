<?php

declare(strict_types=1);

use App\Application\Account\ChangePassword;
use App\Application\Account\DeactivateAccount;
use App\Application\Account\ProfileDetailsInput;
use App\Application\Account\ProfileView;
use App\Application\Account\ReplaceProfileImage;
use App\Application\Account\ResetPassword;
use App\Application\Account\ShowProfileImage;
use App\Application\Account\UpdatePreferences;
use App\Application\Account\UpdateProfile;
use App\Application\Account\VerifyEmail;
use App\Application\Account\ViewProfile;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\ActivityType;
use App\Domain\Account\ImageKind;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\Preferences;
use App\Domain\Account\UnreadableImage;
use App\Domain\Account\WrongPassword;
use Tests\Support\Account\AccountKit;

it('confirms the email once and records it once', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $verify = new VerifyEmail($kit->accounts, $kit->activity, $kit->clock);

    $verify->handle($id);
    $kit->clock->advance('+1 day');
    $verify->handle($id);

    expect($kit->accounts->get($id)->access()->emailVerifiedAt)->toEqual(new DateTimeImmutable('2026-11-25 10:00:00'))
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::EmailVerified]);
});

it('changes the password only with the current one, and checks the new one', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $change = new ChangePassword($kit->accounts, $kit->hasher, $kit->passwords());

    expect(fn () => $change->handle($id, 'wrong horse', 'new password'))->toThrow(WrongPassword::class)
        ->and(fn () => $change->handle($id, 'correct horse', 'short'))->toThrow(InvalidAccountData::class);

    $change->handle($id, 'correct horse', 'new password');

    expect($kit->accounts->get($id)->access()->password->value)->toBe('hashed:new password')
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::PasswordChanged]);
});

it('resets the password without the current one, and records it as a reset', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    new ResetPassword($kit->accounts, $kit->passwords())->handle($id, 'new password');

    expect($kit->accounts->get($id)->access()->password->value)->toBe('hashed:new password')
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered, ActivityType::PasswordReset]);
});

it('updates the name, the phone and the preferences', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    new UpdateProfile($kit->accounts, $kit->activity, $kit->clock)
        ->handle($id, new ProfileDetailsInput('Ana Lima', '(13) 3222-1234', 'home'));
    new UpdatePreferences($kit->accounts, $kit->activity, $kit->clock)->handle($id, new Preferences(true, false));
    $account = $kit->accounts->get($id);

    expect($account->details()->name->value)->toBe('Ana Lima')
        ->and($account->details()->phone->number->digits)->toBe('1332221234')
        ->and($account->profile()->preferences)->toEqual(new Preferences(true, false))
        ->and($kit->activity->typesOf($id))
        ->toBe([ActivityType::Registered, ActivityType::ProfileUpdated, ActivityType::PreferencesUpdated]);
});

it('refuses a profile update with an invalid name before touching the account', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    $update = new UpdateProfile($kit->accounts, $kit->activity, $kit->clock);

    expect(fn () => $update->handle($id, new ProfileDetailsInput('A', '13998765432', 'mobile')))
        ->toThrow(InvalidAccountData::class)
        ->and($kit->activity->typesOf($id))->toBe([ActivityType::Registered]);
});

it('replaces a picture, deletes the one it replaces, and shows the new one', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $replace = new ReplaceProfileImage($kit->accounts, $kit->images, $kit->activity, $kit->clock);
    $show = new ShowProfileImage($kit->accounts, $kit->images);

    expect($show->handle($id, ImageKind::Photo))->toBeNull();

    $replace->handle($id, ImageKind::Photo, 'first');
    $replace->handle($id, ImageKind::Photo, 'second');
    $replace->handle($id, ImageKind::Cover, 'cover');

    expect($show->handle($id, ImageKind::Photo))->toBe('second')
        ->and($show->handle($id, ImageKind::Cover))->toBe('cover')
        ->and($kit->images->files)->toHaveCount(2)
        ->and($kit->activity->typesOf($id))->toBe([
            ActivityType::Registered, ActivityType::PhotoReplaced, ActivityType::PhotoReplaced, ActivityType::CoverReplaced,
        ]);
});

it('refuses a file that is not a picture and keeps the account as it was', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();

    $replace = new ReplaceProfileImage($kit->accounts, $kit->images, $kit->activity, $kit->clock);

    expect(fn () => $replace->handle($id, ImageKind::Cover, 'not an image'))
        ->toThrow(UnreadableImage::class)
        ->and($kit->accounts->get($id)->profile()->images)->toBe([]);
});

it('deactivates only with the password', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $deactivate = new DeactivateAccount($kit->accounts, $kit->hasher, $kit->activity, $kit->clock);

    expect(fn () => $deactivate->handle($id, 'wrong horse'))->toThrow(WrongPassword::class);

    $deactivate->handle($id, 'correct horse');

    expect($kit->accounts->get($id)->access()->reactivationDeadline)->toEqual(new DateTimeImmutable('2026-12-25 10:00:00'));
});

it('shows the profile with its latest ten actions, newest first', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $preferences = new UpdatePreferences($kit->accounts, $kit->activity, $kit->clock);

    foreach (range(1, 11) as $minute) {
        $kit->clock->advance('+1 minute');
        $preferences->handle($id, new Preferences((bool) ($minute % 2), true));
    }

    $profile = new ViewProfile($kit->accounts, $kit->activity)->handle($id);

    expect($profile->account->id)->toEqual($id)
        ->and($profile->activity)->toHaveCount(10)
        ->and($profile->activity[0]->occurredAt)->toEqual(new DateTimeImmutable('2026-11-25 10:11:00'));
});

it('does not find an account that was closed', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $kit->accounts->close($id);

    $view = new ViewProfile($kit->accounts, $kit->activity);

    expect(fn (): ProfileView => $view->handle($id))->toThrow(AccountNotFound::class)
        ->and(fn (): ProfileView => $view->handle(new AccountId(99)))->toThrow(AccountNotFound::class);
});

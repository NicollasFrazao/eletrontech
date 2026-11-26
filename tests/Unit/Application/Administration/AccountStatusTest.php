<?php

declare(strict_types=1);

use App\Application\Administration\CloseAccount;
use App\Application\Administration\DeactivateAccount;
use App\Application\Administration\ReactivateAccount;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\ActivityType;
use App\Domain\Account\ImageKind;
use App\Domain\Account\OwnAccountRefused;
use Tests\Support\Account\AccountKit;

/*
 * The administrator's actions on someone else's account: deactivate, reactivate, delete (close).
 */

function deactivator(AccountKit $kit): DeactivateAccount
{
    return new DeactivateAccount($kit->accounts, $kit->activity, $kit->clock);
}

function closer(AccountKit $kit): CloseAccount
{
    return new CloseAccount($kit->accounts, $kit->closer());
}

function reactivator(AccountKit $kit): ReactivateAccount
{
    return new ReactivateAccount($kit->accounts, $kit->activity, $kit->clock);
}

it('deactivates an account for 30 days without its password, and records it', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');

    deactivator($kit)->handle($administrator, $member);

    expect($kit->accounts->get($member)->access()->reactivationDeadline)->toEqual(new DateTimeImmutable('2026-12-25 10:00:00'))
        ->and($kit->activity->typesOf($member))->toBe([ActivityType::Registered, ActivityType::Deactivated]);
});

it('keeps the deadline of an account deactivated already', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');
    deactivator($kit)->handle($administrator, $member);
    $kit->clock->advance('+5 days');

    deactivator($kit)->handle($administrator, $member);

    expect($kit->accounts->get($member)->access()->reactivationDeadline)->toEqual(new DateTimeImmutable('2026-12-25 10:00:00'))
        ->and($kit->activity->typesOf($member))->toBe([ActivityType::Registered, ActivityType::Deactivated]);
});

it('reactivates a deactivated account and records it, and leaves an active one alone', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');
    deactivator($kit)->handle($administrator, $member);

    reactivator($kit)->handle($member);
    reactivator($kit)->handle($member);

    expect($kit->accounts->get($member)->isActive())->toBeTrue()
        ->and($kit->activity->typesOf($member))
        ->toBe([ActivityType::Registered, ActivityType::Deactivated, ActivityType::Reactivated]);
});

it('reactivates an account whose deadline passed but which is not closed yet', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');
    deactivator($kit)->handle($administrator, $member);
    $kit->clock->advance('+31 days');

    reactivator($kit)->handle($member);

    expect($kit->accounts->get($member)->isActive())->toBeTrue()
        ->and($kit->activity->typesOf($member))
        ->toBe([ActivityType::Registered, ActivityType::Deactivated, ActivityType::Reactivated]);
});

it('closes an account: erased, its pictures deleted, and recorded', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');
    $account = $kit->accounts->get($member);
    $path = $kit->images->store(ImageKind::Photo, 'photo');
    $account->replaceProfile($account->profile()->withImage(ImageKind::Photo, $path));
    $kit->accounts->save($account);

    closer($kit)->handle($administrator, $member);

    expect($kit->accounts->closed)->toBe([$member->value])
        ->and($kit->images->read($path))->toBeNull()
        ->and($kit->activity->typesOf($member))->toBe([ActivityType::Registered, ActivityType::Closed]);
});

it('refuses to deactivate or close the administrator\'s own account', function (): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();

    expect(fn () => deactivator($kit)->handle($administrator, $administrator))->toThrow(OwnAccountRefused::class)
        ->and(fn () => closer($kit)->handle($administrator, $administrator))
        ->toThrow(OwnAccountRefused::class)
        ->and($kit->accounts->get($administrator)->isActive())->toBeTrue();
});

it('finds no account that is closed or does not exist', function (Closure $action): void {
    $kit = new AccountKit;
    $administrator = $kit->registered();
    $member = $kit->registered('bia@example.com', '111.444.777-35');
    $kit->accounts->close($member);

    expect(fn () => $action($kit, $administrator, $member))->toThrow(AccountNotFound::class)
        ->and(fn () => $action($kit, $administrator, new AccountId(99)))->toThrow(AccountNotFound::class);
})->with([
    'deactivate' => [fn (AccountKit $kit, AccountId $administrator, AccountId $id) => deactivator($kit)->handle($administrator, $id)],
    'reactivate' => [fn (AccountKit $kit, AccountId $administrator, AccountId $id) => reactivator($kit)->handle($id)],
    'close' => [fn (AccountKit $kit, AccountId $administrator, AccountId $id) => closer($kit)->handle($administrator, $id)],
]);

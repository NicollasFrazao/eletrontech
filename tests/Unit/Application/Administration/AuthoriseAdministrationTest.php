<?php

declare(strict_types=1);

use App\Application\Account\VerifyEmail;
use App\Application\Administration\AuthoriseAdministration;
use App\Domain\Account\AccountId;
use App\Domain\Account\Role;
use Tests\Support\Account\AccountKit;

it('lets a confirmed administrator in', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    new VerifyEmail($kit->accounts, $kit->activity, $kit->clock)->handle($id);
    $kit->accounts->grant($id, Role::Administrator);

    expect(new AuthoriseAdministration($kit->accounts)->handle($id))->toBeTrue();
});

it('keeps a member out, confirmed or not', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $authorise = new AuthoriseAdministration($kit->accounts);

    expect($authorise->handle($id))->toBeFalse();

    new VerifyEmail($kit->accounts, $kit->activity, $kit->clock)->handle($id);

    expect($authorise->handle($id))->toBeFalse();
});

it('keeps out an administrator whose email is not confirmed', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    $kit->accounts->grant($id, Role::Administrator);

    expect(new AuthoriseAdministration($kit->accounts)->handle($id))->toBeFalse();
});

it('keeps out an account that is closed or does not exist', function (): void {
    $kit = new AccountKit;
    $id = $kit->registered();
    new VerifyEmail($kit->accounts, $kit->activity, $kit->clock)->handle($id);
    $kit->accounts->grant($id, Role::Administrator);
    $kit->accounts->close($id);
    $authorise = new AuthoriseAdministration($kit->accounts);

    expect($authorise->handle($id))->toBeFalse()
        ->and($authorise->handle(new AccountId(99)))->toBeFalse();
});

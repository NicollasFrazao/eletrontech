<?php

declare(strict_types=1);

use App\Domain\Account\Role;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Account\SignedUp;

/*
 * E8's first rule: the admin panel at /admin opens for administrators only.
 */
uses(RefreshDatabase::class);

function administrator(User $user): User
{
    $user->forceFill(['role' => Role::Administrator->value])->save();

    return $user->refresh();
}

it('opens the panel to a confirmed administrator', function (): void {
    $this->actingAs(administrator(SignedUp::verified()))->get('/admin')->assertOk();
});

it('refuses a confirmed member', function (): void {
    $user = SignedUp::verified();

    expect($user->role)->toBe(Role::Member->value);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('refuses an unconfirmed account, an administrator or not', function (): void {
    $this->actingAs(SignedUp::unverified())->get('/admin')->assertForbidden();
    $this->actingAs(administrator(SignedUp::unverified('bia@example.com', '111.444.777-35')))->get('/admin')->assertForbidden();
});

it('sends a guest to the sign-in page', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('refuses an administrator whose account was closed meanwhile', function (): void {
    $user = administrator(SignedUp::verified());
    $user->forceFill(['closed_at' => now()])->save();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

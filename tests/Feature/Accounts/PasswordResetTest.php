<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

it('shows the forms', function (): void {
    $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?');
    $this->get('/reset-password/a-token?email=ana@example.com')->assertOk()->assertSee('value="ana@example.com"', false);
});

it('sends a reset link to an account, and answers the same for an unknown email', function (): void {
    Notification::fake();
    $user = SignedUp::verified();
    $status = 'If an account uses this email, a link to reset the password was sent to it.';

    $this->post('/forgot-password', ['email' => 'Ana@example.com'])->assertSessionHas('status', $status);
    $this->post('/forgot-password', ['email' => 'bia@example.com'])->assertSessionHas('status', $status);

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentTimes(ResetPassword::class, 1);
});

it('sets the new password with a valid link and records it as a reset', function (): void {
    $user = SignedUp::verified();
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'ana@example.com',
        'password' => 'new password',
        'password_confirmation' => 'new password',
    ])->assertRedirect('/login');

    expect(Hash::check('new password', (string) User::query()->findOrFail($user->id)->password))->toBeTrue();
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'password_reset']);
});

it('refuses an invalid token and a new password that is too short', function (): void {
    $user = SignedUp::verified();
    $form = ['email' => 'ana@example.com', 'password' => 'new password', 'password_confirmation' => 'new password'];

    $this->post('/reset-password', [...$form, 'token' => 'forged'])
        ->assertSessionHasErrors(['email' => 'This link is invalid or has expired. Ask for a new one.']);
    $short = ['password' => 'short', 'password_confirmation' => 'short'];
    $this->post('/reset-password', [...$form, ...$short, 'token' => Password::createToken($user)])
        ->assertSessionHasErrors('password');

    expect(Hash::check('correct horse', (string) User::query()->findOrFail($user->id)->password))->toBeTrue();
});

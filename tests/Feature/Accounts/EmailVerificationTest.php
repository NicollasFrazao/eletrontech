<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

function verificationLink(int $id, string $email): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $id, 'hash' => sha1($email)]);
}

it('sends an account that has not confirmed its email to the notice page', function (): void {
    $this->actingAs(SignedUp::unverified())->get('/profile')->assertRedirect('/email/verify');
    $this->get('/email/verify')->assertOk()->assertSee('Confirm your email');
});

it('confirms the email through the signed link and records it', function (): void {
    $user = SignedUp::unverified();

    $this->actingAs($user)->get(verificationLink($user->id, 'ana@example.com'))->assertRedirect('/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'email_verified']);
});

it('refuses a link for another address or without a valid signature', function (): void {
    $user = SignedUp::unverified();

    $this->actingAs($user)->get(verificationLink($user->id, 'bia@example.com'))->assertForbidden();
    $this->get("/email/verify/{$user->id}/".sha1('ana@example.com'))->assertForbidden();

    expect($user->refresh()->email_verified_at)->toBeNull();
});

it('sends a new link on request', function (): void {
    Notification::fake();
    $user = SignedUp::unverified();

    $this->actingAs($user)->from('/email/verify')->post('/email/verification-notification')->assertRedirect('/email/verify');

    Notification::assertSentTo($user, VerifyEmail::class);
});

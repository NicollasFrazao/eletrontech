<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

it('shows the sign-in form', function (): void {
    $this->get('/login')->assertOk()->assertSee('Sign in')->assertSee('name="remember"', false);
});

it('signs in, renews the session and goes to the profile', function (): void {
    $user = SignedUp::verified();

    $this->post('/login', ['email' => 'ANA@example.com', 'password' => 'correct horse'])->assertRedirect('/profile');

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'signed_in']);
});

it('keeps the session with "keep me signed in"', function (): void {
    SignedUp::verified();

    $response = $this->post('/login', ['email' => 'ana@example.com', 'password' => 'correct horse', 'remember' => '1']);

    $response->assertRedirect('/profile');
    $cookies = collect($response->headers->getCookies());

    expect($cookies->contains(fn ($cookie): bool => str_starts_with($cookie->getName(), 'remember_web_')))->toBeTrue();
});

it('answers a wrong password and an unknown email the same way', function (string $email, string $password): void {
    SignedUp::verified();

    $this->from('/login')->post('/login', ['email' => $email, 'password' => $password])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'The email or the password is wrong.']);

    $this->assertGuest();
})->with([
    'wrong password' => ['ana@example.com', 'wrong horse'],
    'unknown email' => ['bia@example.com', 'correct horse'],
]);

it('stops counting attempts after five failures for the email and address', function (): void {
    SignedUp::verified();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'ana@example.com', 'password' => 'wrong horse']);
    }

    $this->post('/login', ['email' => 'ana@example.com', 'password' => 'correct horse'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(session('errors')->first('email'))->toStartWith('Too many attempts.');
});

it('signs out, ends the session and records it', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'signed_out']);
});

it('reactivates a deactivated account signed in to within 30 days', function (): void {
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/profile/deactivate', ['deactivation_password' => 'correct horse']);

    $this->travel(29)->days();
    $this->post('/login', ['email' => 'ana@example.com', 'password' => 'correct horse'])->assertRedirect('/profile');

    expect($user->refresh()->reactivation_deadline)->toBeNull();
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'reactivated']);
});

it('closes a deactivated account signed in to after 30 days and erases what identifies the person', function (): void {
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/profile/deactivate', ['deactivation_password' => 'correct horse']);

    $this->travel(31)->days();
    $this->post('/login', ['email' => 'ana@example.com', 'password' => 'correct horse'])->assertSessionHasErrors('email');

    $this->assertGuest();
    $closed = User::query()->findOrFail($user->id);
    expect($closed->closed_at)->not->toBeNull()
        ->and([$closed->email, $closed->cpf, $closed->phone_number, $closed->password])->toBe([null, null, null, null])
        ->and($closed->name)->toBe('Ana Souza');
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'closed']);
});

it('lets the email and CPF of an account closed by its deadline sign up again', function (): void {
    $old = SignedUp::verified();
    $this->actingAs($old)->post('/profile/deactivate', ['deactivation_password' => 'correct horse']);
    $this->travel(31)->days();

    $new = SignedUp::unverified();

    expect($new->id)->not->toBe($old->id)
        ->and($old->refresh()->closed_at)->not->toBeNull();
});

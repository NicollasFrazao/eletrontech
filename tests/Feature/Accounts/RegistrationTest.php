<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

function signUpForm(array $override = []): array
{
    return [
        'name' => 'Ana Souza',
        'cpf' => '529.982.247-25',
        'phone_number' => '(13) 99876-5432',
        'phone_type' => 'mobile',
        'email' => 'Ana@Example.com',
        'password' => 'correct horse',
        'password_confirmation' => 'correct horse',
        ...$override,
    ];
}

it('shows the sign-up form', function (): void {
    $this->get('/register')->assertOk()->assertSee('Create an account')->assertSee('name="cpf"', false);
});

it('registers, signs in, sends the confirmation link and hashes the password', function (): void {
    Notification::fake();

    $this->post('/register', signUpForm())->assertRedirect('/email/verify');

    $user = User::query()->sole();
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->email)->toBe('ana@example.com')
        ->and($user->cpf)->toBe('52998224725')
        ->and($user->phone_number)->toBe('13998765432')
        ->and($user->password)->not->toBe('correct horse')
        ->and(Hash::check('correct horse', (string) $user->password))->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'registered']);
});

it('refuses each invalid value on its own field', function (array $override, string $field): void {
    $this->from('/register')->post('/register', signUpForm($override))
        ->assertRedirect('/register')
        ->assertSessionHasErrors($field);

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
})->with([
    'CPF' => [['cpf' => '529.982.247-24'], 'cpf'],
    'phone' => [['phone_number' => '(13) 3222-1234'], 'phone_number'],
    'email' => [['email' => 'ana@'], 'email'],
    'name' => [['name' => 'A'], 'name'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'confirmation' => [['password_confirmation' => 'other horse'], 'password'],
]);

it('refuses an email or a CPF another account holds', function (array $override, string $field, string $message): void {
    SignedUp::verified();

    $this->post('/register', signUpForm($override))->assertSessionHasErrors([$field => $message]);
})->with([
    'email' => [['cpf' => '123.456.789-09'], 'email', 'An account already uses this email.'],
    'CPF' => [['email' => 'bia@example.com'], 'cpf', 'An account already uses this CPF.'],
]);

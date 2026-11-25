<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

it('sends a guest to the sign-in page', function (string $method, string $uri): void {
    $this->call($method, $uri)->assertRedirect('/login');
})->with([
    ['GET', '/profile'],
    ['PUT', '/profile/details'],
    ['PUT', '/profile/password'],
    ['PUT', '/profile/preferences'],
    ['POST', '/profile/deactivate'],
    ['GET', '/profile/images/photo'],
]);

it('sends a signed-in person away from the guest pages', function (): void {
    $this->actingAs(SignedUp::verified())->get('/login')->assertRedirect('/profile');
});

it('shows the details, the preferences and the recent activity', function (): void {
    $this->actingAs(SignedUp::verified())->get('/profile')
        ->assertOk()
        ->assertSee('Ana Souza')
        ->assertSee('52998224725')
        ->assertSee('ana@example.com')
        ->assertSee('Email confirmed')
        ->assertSee('Account created');
});

it('changes the name and the phone, never the CPF or the email', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->put('/profile/details', [
        'name' => 'Ana Lima',
        'phone_number' => '(13) 3222-1234',
        'phone_type' => 'home',
        'cpf' => '123.456.789-09',
        'email' => 'other@example.com',
    ])->assertRedirect('/profile')->assertSessionHas('status', 'Your details were saved.');

    $user->refresh();
    expect([$user->name, $user->phone_number, $user->phone_type, $user->cpf, $user->email])
        ->toBe(['Ana Lima', '1332221234', 'home', '52998224725', 'ana@example.com']);
});

it('refuses a mobile phone type with a landline number', function (): void {
    $this->actingAs(SignedUp::verified())
        ->put('/profile/details', ['name' => 'Ana', 'phone_number' => '1332221234', 'phone_type' => 'mobile'])
        ->assertSessionHasErrors('phone_number');
});

it('changes the password only with the current one, and ends "keep me signed in" cookies', function (): void {
    $user = SignedUp::verified();
    $user->forceFill(['remember_token' => 'old-token'])->save();

    $change = ['password' => 'new password', 'password_confirmation' => 'new password'];

    $this->actingAs($user)->put('/profile/password', [...$change, 'current_password' => 'wrong horse'])
        ->assertSessionHasErrors(['current_password' => 'The password is wrong.']);
    $this->put('/profile/password', [...$change, 'current_password' => 'correct horse'])
        ->assertRedirect('/profile');

    $user = User::query()->findOrFail($user->id);
    expect(Hash::check('new password', (string) $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-token');
});

it('saves the night mode and the tutorial toggle, an unticked box meaning off', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->put('/profile/preferences', ['is_night_mode' => '1'])->assertRedirect('/profile');

    $user->refresh();
    expect([$user->is_night_mode, $user->shows_tutorial])->toBe([true, false]);
    $this->get('/profile')->assertSee('<body class="night">', false);
});

it('deactivates the account with the password and signs out', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->post('/profile/deactivate', ['deactivation_password' => 'wrong horse'])
        ->assertSessionHasErrors(['deactivation_password' => 'The password is wrong.']);
    $this->post('/profile/deactivate', ['deactivation_password' => 'correct horse'])->assertRedirect('/');

    $this->assertGuest();
    expect($user->refresh()->reactivation_deadline?->toDateString())->toBe(now()->addDays(30)->toDateString());
});

it('ends the session of an account closed meanwhile', function (): void {
    $user = SignedUp::verified();
    User::query()->whereKey($user->id)->update(['closed_at' => now()]);

    $this->actingAs($user)->get('/profile')->assertRedirect('/login');

    $this->assertGuest();
});

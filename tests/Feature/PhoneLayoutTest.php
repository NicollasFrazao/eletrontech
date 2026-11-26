<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Account\SignedUp;
use Tests\Support\Licensing\Licensed;

/*
 * E9 (#62): the user area and the shop carry their phone layout, a single column with 44 px touch
 * targets and no horizontal scroll, on top of the desktop one.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

it('serves the sign-up, sign-in and password pages ready for a phone-sized screen', function (string $uri): void {
    $this->get($uri)
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('@media (max-width: 40rem)', false)
        ->assertSee('min-height: 44px', false)
        ->assertSee('overflow-wrap: anywhere', false);
})->with([
    'sign-up' => '/register',
    'sign-in' => '/login',
    'forgot password' => '/forgot-password',
]);

it('serves the pages behind sign-in ready for a phone-sized screen', function (Closure $uri): void {
    $this->actingAs(SignedUp::verified())->get($uri())
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('@media (max-width: 40rem)', false)
        ->assertSee('min-height: 44px', false)
        ->assertSee('overflow-wrap: anywhere', false);
})->with([
    'profile and preferences' => fn (): string => '/profile',
    'shop' => fn (): string => '/shop',
    'package' => fn (): string => '/shop/packages/'.Licensed::packageId('Light'),
    'custom package' => fn (): string => '/shop/custom',
    'licence' => fn (): string => '/licence',
    'agenda' => fn (): string => '/agenda',
    'file manager' => fn (): string => '/files',
    'feedback' => fn (): string => '/feedback',
]);

it('keeps the desktop layout outside the phone breakpoint', function (): void {
    $page = $this->get('/login')->assertOk()->getContent();

    expect($page)->toContain('main { max-width: 40rem; margin: 0 auto; padding: 3rem 1rem; }')
        ->and($page)->toContain('max-width: 24rem;');
});

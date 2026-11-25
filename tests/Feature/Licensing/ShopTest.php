<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\LicenceRecord;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Account\SignedUp;
use Tests\Support\Licensing\Licensed;

uses(RefreshDatabase::class);

/**
 * The licence row of the account.
 */
function licenceOf(User $user): LicenceRecord
{
    return LicenceRecord::query()->with('package')->findOrFail($user->id);
}

it('gives a new account the Basic package for seven days', function (): void {
    $user = SignedUp::verified();

    $licence = licenceOf($user);
    expect($licence->package->name)->toBe('Basic')
        ->and($licence->starts_at->diffInDays($licence->ends_at))->toEqual(7.0);
});

it('lists the ready-made packages with their tools, without the free one', function (): void {
    $this->actingAs(SignedUp::verified())->get('/shop')
        ->assertOk()
        ->assertSeeInOrder(['Advanced', 'Golden', 'Light', 'Measure', 'Tester', 'Custom package'])
        ->assertSee("Ohm's law")
        ->assertDontSee('<h2>Basic</h2>', false);
});

it('shows a package with its tools and the purchase form, and not the free one', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->get('/shop/packages/'.Licensed::packageId('Light'))
        ->assertOk()
        ->assertSee('General lighting')
        ->assertSee('name="purchase_password"', false)
        ->assertSee('min="7" max="365"', false);
    $this->actingAs($user)->get('/shop/packages/'.Licensed::packageId('Basic'))->assertNotFound();
    $this->actingAs($user)->get('/shop/packages/999')->assertNotFound();
});

it('buys a package with the password and shows the licence', function (): void {
    $user = SignedUp::verified();
    $light = Licensed::packageId('Light');

    $this->actingAs($user)
        ->post('/shop/purchases', ['package_id' => $light, 'days' => 30, 'purchase_password' => 'correct horse'])
        ->assertRedirect('/licence');

    expect(licenceOf($user)->package_id)->toBe($light);
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'package_bought']);
    $this->actingAs($user)->get('/licence')
        ->assertSee('Light')
        ->assertSee('30 days left')
        ->assertSee('Lighting power');
});

it('extends the same package bought again while it runs', function (): void {
    $user = SignedUp::verified();
    Licensed::buy($user, Licensed::packageId('Light'), 30);
    $this->travel(10)->days();

    Licensed::buy($user, Licensed::packageId('Light'), 7);

    $this->actingAs($user)->get('/licence')->assertSee('27 days left');
});

it('builds a custom package from the tools picked', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->get('/shop/custom')->assertOk()->assertSee('value="ohms-law"', false);
    $this->actingAs($user)->post('/shop/purchases', [
        'tools' => ['ohms-law', 'outlet-count'],
        'days' => 7,
        'purchase_password' => 'correct horse',
    ])->assertRedirect('/licence');

    $custom = PackageRecord::query()->where('owner_id', $user->id)->sole();
    expect(licenceOf($user)->package_id)->toBe($custom->id)
        ->and($custom->tools->pluck('id')->all())->toBe(['ohms-law', 'outlet-count']);
    $this->actingAs($user)->postJson('/tools/calculators/ohms-law', ['voltage' => '10', 'current' => '2'])->assertOk();
});

it('puts each refused purchase on its field and keeps the licence', function (array $form, string $field, string $message): void {
    $user = SignedUp::verified();
    $before = licenceOf($user)->package_id;

    $this->actingAs($user)->from('/shop')
        ->post('/shop/purchases', [...['days' => 30, 'purchase_password' => 'correct horse'], ...$form])
        ->assertRedirect('/shop')
        ->assertSessionHasErrors([$field => $message]);

    expect(licenceOf($user)->package_id)->toBe($before);
})->with([
    'wrong password' => [['package_id' => 2, 'purchase_password' => 'wrong horse'], 'purchase_password', 'The password is wrong.'],
    'too few days' => [['package_id' => 2, 'days' => 6], 'days', 'Choose from 7 to 365 days.'],
    'too many days' => [['package_id' => 2, 'days' => 366], 'days', 'Choose from 7 to 365 days.'],
    'the free package' => [['package_id' => 1], 'package_id', 'This package is not for sale.'],
    'no such package' => [['package_id' => 999], 'package_id', 'This package is not for sale.'],
    'a tool that does not exist' => [['tools' => ['time-machine']], 'tools', 'Pick at least one tool from the list.'],
    'no tool' => [['tools' => []], 'tools', 'The tools field is required when package id is not present.'],
]);

it('refuses another person\'s custom package by its id', function (): void {
    $ana = SignedUp::verified();
    $bia = SignedUp::verified('bia@example.com', '123.456.789-09');
    $this->actingAs($bia)->post('/shop/purchases', ['tools' => ['ohms-law'], 'days' => 7, 'purchase_password' => 'correct horse']);
    $custom = PackageRecord::query()->where('owner_id', $bia->id)->sole()->id;

    $this->actingAs($ana)->post('/shop/purchases', ['package_id' => $custom, 'days' => 7, 'purchase_password' => 'correct horse'])
        ->assertSessionHasErrors('package_id');
});

it('ignores a buyer id sent with the form', function (): void {
    $ana = SignedUp::verified();
    $bia = SignedUp::verified('bia@example.com', '123.456.789-09');

    $this->actingAs($ana)->post('/shop/purchases', [
        'package_id' => Licensed::packageId('Light'),
        'days' => 7,
        'purchase_password' => 'correct horse',
        'user_id' => $bia->id,
    ]);

    expect(licenceOf($bia)->package->name)->toBe('Basic')
        ->and(licenceOf($ana)->package->name)->toBe('Light');
});

it('shows an ended licence and an account without one', function (): void {
    $user = SignedUp::verified();
    $this->travel(8)->days();

    $this->actingAs($user)->get('/licence')->assertSee('This licence has ended.');

    LicenceRecord::query()->whereKey($user->id)->delete();
    $this->actingAs($user)->get('/licence')->assertSee('You have no licence yet.');
});

it('keeps the shop and the licence to signed-in, confirmed accounts', function (string $uri): void {
    $this->get($uri)->assertRedirect('/login');
    $this->actingAs(SignedUp::unverified())->get($uri)->assertRedirect('/email/verify');
})->with(['/shop', '/shop/custom', '/licence']);

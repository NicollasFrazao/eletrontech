<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\LicenceRecord;
use App\Infrastructure\Persistence\Eloquent\ToolUseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Account\SignedUp;
use Tests\Support\Licensing\Licensed;

/*
 * E5's rule: opening a tool outside the person's licence is refused, by the `use-tool` gate alone.
 */
uses(RefreshDatabase::class);

it('lets a new account use the tools of the free package, and records each use', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->postJson('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])->assertOk();
    $this->actingAs($user)->postJson('/tools/unit-converter', ['value' => '1', 'from' => 'kilowatt', 'to' => 'watt'])->assertOk();

    expect(ToolUseRecord::query()->where('user_id', $user->id)->orderBy('id')->pluck('tool_id')->all())
        ->toBe(['basic-calculator', 'unit-converter']);
});

it('refuses a tool the package does not cover, and records nothing', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->postJson('/tools/calculators/ohms-law', ['voltage' => '10', 'current' => '2'])
        ->assertForbidden()
        ->assertJson(['message' => 'Your licence does not cover this tool, or it has ended.']);

    expect(ToolUseRecord::query()->count())->toBe(0);
});

it('refuses every tool once the licence has ended', function (): void {
    $user = SignedUp::verified();

    $this->travel(7)->days();

    $this->actingAs($user)->postJson('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])->assertForbidden();
    $this->actingAs($user)->postJson('/tools/unit-converter', ['value' => '1', 'from' => 'kilowatt', 'to' => 'watt'])->assertForbidden();
});

it('refuses an account without a licence, as one from before E5', function (): void {
    $user = SignedUp::verified();
    LicenceRecord::query()->whereKey($user->id)->delete();

    $this->actingAs($user)->postJson('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])->assertForbidden();
});

it('opens a bought package\'s tools and keeps the rest closed', function (): void {
    $user = SignedUp::verified();
    Licensed::buy($user, Licensed::packageId('Tester'), 7);

    $this->actingAs($user)->postJson('/tools/calculators/ohms-law', ['voltage' => '10', 'current' => '2'])->assertOk();
    $this->actingAs($user)->postJson('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])->assertForbidden();
});

it('answers not found for a tool that does not exist, and for a tool that is not a calculator', function (): void {
    $user = Licensed::golden();

    $this->actingAs($user)->postJson('/tools/calculators/time-machine', [])->assertNotFound();
    $this->actingAs($user)->postJson('/tools/calculators/unit-converter', [])->assertNotFound();
});

it('sends a guest to sign in and an unconfirmed account to confirm its email', function (): void {
    $this->post('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])->assertRedirect('/login');

    $this->actingAs(SignedUp::unverified())->post('/tools/calculators/basic-calculator', ['expression' => '1 + 1'])
        ->assertRedirect('/email/verify');
});

it('keeps the list of calculators and the converter form open to everyone', function (): void {
    $this->getJson('/tools/calculators')->assertOk();
    $this->get('/tools/unit-converter')->assertOk();
});

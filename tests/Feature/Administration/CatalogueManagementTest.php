<?php

declare(strict_types=1);

use App\Domain\Account\Role;
use App\Infrastructure\Filament\Packages\CreatePackage;
use App\Infrastructure\Filament\Packages\EditPackage;
use App\Infrastructure\Filament\Packages\ListPackages;
use App\Infrastructure\Filament\Tools\EditTool;
use App\Infrastructure\Filament\Tools\ListTools;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\Account\SignedUp;

/*
 * E8's catalogue (#58): the tools and the packages of the licensing domain in the admin panel, created
 * and changed through the use cases, so the domain's rules (7 to 365 days, at least one tool, a price
 * from 0.00) hold. The packages are the ones the migrations insert.
 */
uses(RefreshDatabase::class);

/**
 * A confirmed administrator, signed in to the panel.
 */
function catalogueAdministrator(): User
{
    $user = SignedUp::verified();
    $user->forceFill(['role' => Role::Administrator->value])->save();
    test()->actingAs($user->refresh());

    return $user;
}

/**
 * The package the migrations inserted under the name.
 */
function packageNamed(string $name): PackageRecord
{
    return PackageRecord::query()->with('tools')->where('name', $name)->firstOrFail();
}

it('refuses the catalogue pages to a member', function (): void {
    $this->actingAs(SignedUp::verified());
    $tester = packageNamed('Tester');

    $this->get('/admin/packages')->assertForbidden();
    $this->get('/admin/packages/create')->assertForbidden();
    $this->get("/admin/packages/{$tester->id}/edit")->assertForbidden();
    $this->get('/admin/tools')->assertForbidden();
    $this->get('/admin/tools/ohms-law/edit')->assertForbidden();
});

it('lists the free and ready-made packages with their price and days, not custom ones', function (): void {
    $administrator = catalogueAdministrator();
    $custom = PackageRecord::query()->create(['name' => 'Custom', 'kind' => 'custom', 'owner_id' => $administrator->id]);

    $this->get('/admin/packages')->assertOk()->assertSee('Packages');

    Livewire::test(ListPackages::class)
        ->assertCanSeeTableRecords([packageNamed('Basic'), packageNamed('Tester'), packageNamed('Golden')])
        ->assertCanNotSeeTableRecords([$custom])
        ->assertTableColumnStateSet('days', 30, packageNamed('Tester'));
});

it('creates a ready-made package with its tools, the everyday ones, its price and its days', function (): void {
    catalogueAdministrator();

    Livewire::test(CreatePackage::class)
        ->fillForm(['name' => ' Power ', 'tools' => ['general-power', 'outlet-count'], 'price' => '49.9', 'days' => '90'])
        ->call('create')
        ->assertHasNoFormErrors();

    $power = packageNamed('Power');
    expect($power->kind)->toBe('ready_made')
        ->and($power->price)->toBe('49.90')
        ->and($power->days)->toBe(90)
        ->and($power->tools->modelKeys())->toEqualCanonicalizing([
            'basic-calculator', 'unit-converter', 'rule-of-three', 'general-power', 'outlet-count',
        ]);
});

it('refuses each value the licensing domain refuses, on its field, and creates nothing', function (array $values, string $field): void {
    catalogueAdministrator();
    $before = PackageRecord::query()->count();

    Livewire::test(CreatePackage::class)
        ->fillForm([...['name' => 'Power', 'tools' => ['general-power'], 'price' => '10', 'days' => '30'], ...$values])
        ->call('create')
        ->assertHasFormErrors([$field]);

    expect(PackageRecord::query()->count())->toBe($before);
})->with([
    '6 days' => [['days' => '6'], 'days'],
    '366 days' => [['days' => '366'], 'days'],
    'days in words' => [['days' => 'thirty'], 'days'],
    'negative price' => [['price' => '-5'], 'price'],
    'three decimal places' => [['price' => '1.999'], 'price'],
    'no tool' => [['tools' => []], 'tools'],
    'a name of 101 characters' => [['name' => str_repeat('a', 101)], 'name'],
]);

it('edits a ready-made package: its name, its tools, its price and its days', function (): void {
    catalogueAdministrator();
    $tester = packageNamed('Tester');

    Livewire::test(EditPackage::class, ['record' => $tester->getRouteKey()])
        ->assertSchemaStateSet(['name' => 'Tester', 'price' => '0.00', 'days' => 30])
        ->fillForm(['name' => 'Tester Pro', 'tools' => ['ohms-law', 'general-power'], 'price' => '19.90', 'days' => '365'])
        ->call('save')
        ->assertHasNoFormErrors();

    $tester = packageNamed('Tester Pro');
    expect([$tester->price, $tester->days])->toBe(['19.90', 365])
        ->and($tester->tools->modelKeys())->toEqualCanonicalizing([
            'basic-calculator', 'unit-converter', 'rule-of-three', 'ohms-law', 'general-power',
        ]);
});

it('refuses days out of range on an edit and leaves the package as it was', function (): void {
    catalogueAdministrator();
    $tester = packageNamed('Tester');

    Livewire::test(EditPackage::class, ['record' => $tester->getRouteKey()])
        ->fillForm(['name' => 'Tester Pro', 'days' => '400'])
        ->call('save')
        ->assertHasFormErrors(['days']);

    expect(packageNamed('Tester')->days)->toBe(30);
});

it('edits the free package\'s name and tools, with no price or days since it is not sold', function (): void {
    catalogueAdministrator();
    $basic = packageNamed('Basic');

    Livewire::test(EditPackage::class, ['record' => $basic->getRouteKey()])
        ->assertFormFieldHidden('price')
        ->assertFormFieldHidden('days')
        ->fillForm(['name' => 'Starter', 'tools' => ['ohms-law']])
        ->call('save')
        ->assertHasNoFormErrors();

    $basic->refresh()->load('tools');
    expect([$basic->name, $basic->kind, $basic->price, $basic->days])->toBe(['Starter', 'free', null, null])
        ->and($basic->tools->modelKeys())->toEqualCanonicalizing(['basic-calculator', 'unit-converter', 'rule-of-three', 'ohms-law']);
});

it('does not open a person\'s custom package', function (): void {
    $administrator = catalogueAdministrator();
    $custom = PackageRecord::query()->create(['name' => 'Custom', 'kind' => 'custom', 'owner_id' => $administrator->id]);

    $this->get("/admin/packages/{$custom->id}/edit")->assertNotFound();
});

it('lists the tools and renames one', function (): void {
    catalogueAdministrator();
    $ohm = ToolRecord::query()->findOrFail('ohms-law');

    $this->get('/admin/tools')->assertOk()->assertSee('Tools');
    Livewire::test(ListTools::class)->searchTable('ohm')->assertCanSeeTableRecords([$ohm]);

    Livewire::test(EditTool::class, ['record' => $ohm->getRouteKey()])
        ->assertSchemaStateSet(['id' => 'ohms-law', 'name' => "Ohm's law"])
        ->fillForm(['name' => " Ohm's first law ", 'id' => 'changed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ohm->refresh()->name)->toBe("Ohm's first law")
        ->and(ToolRecord::query()->whereKey('changed')->exists())->toBeFalse();
});

it('refuses a tool name another tool has, or a blank one', function (string $name): void {
    catalogueAdministrator();

    Livewire::test(EditTool::class, ['record' => 'ohms-law'])
        ->fillForm(['name' => $name])
        ->call('save')
        ->assertHasFormErrors(['name']);

    expect(ToolRecord::query()->findOrFail('ohms-law')->name)->toBe("Ohm's law");
})->with(['taken' => 'Unit converter', 'blank' => '   ', '101 characters' => str_repeat('a', 101)]);

it('offers no way to create a tool', function (): void {
    catalogueAdministrator();

    $this->get('/admin/tools/create')->assertNotFound();
});

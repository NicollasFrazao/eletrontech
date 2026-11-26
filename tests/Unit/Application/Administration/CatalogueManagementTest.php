<?php

declare(strict_types=1);

use App\Application\Administration\ChangePackage;
use App\Application\Administration\CreatePackage;
use App\Application\Administration\PackageInput;
use App\Application\Administration\RenameTool;
use App\Domain\Account\AccountId;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageTerms;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolNameTaken;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use Tests\Support\Licensing\InMemoryPackageRepository;
use Tests\Support\Licensing\InMemoryToolRepository;

/*
 * The catalogue use cases of the admin panel (#58) on the in-memory adapters: packages 1 Basic (free),
 * 2 Tester and 3 Golden.
 */

/**
 * The tool identifiers of a package.
 *
 * @return list<string>
 */
function toolsOfPackage(InMemoryPackageRepository $packages, int $id): array
{
    return array_map(static fn (ToolId $tool): string => $tool->value, $packages->get(new PackageId($id))->tools());
}

it('creates a ready-made package with the everyday tools, its price and its days', function (): void {
    $packages = new InMemoryPackageRepository;

    $id = new CreatePackage($packages)->handle(new PackageInput(' Power ', ['general-power'], '49.9', 30));

    $package = $packages->get($id);
    expect($package->name)->toBe('Power')
        ->and($package->kind->value)->toBe('ready_made')
        ->and(toolsOfPackage($packages, $id->value))->toBe(['basic-calculator', 'unit-converter', 'rule-of-three', 'general-power'])
        ->and((string) $packages->terms[$id->value]?->price->amount)->toBe('49.90')
        ->and($packages->terms[$id->value]?->days->value)->toBe(30);
});

it('refuses each value the licensing domain refuses, and stores nothing', function (PackageInput $input, string $error): void {
    $packages = new InMemoryPackageRepository;

    expect(static fn (): PackageId => new CreatePackage($packages)->handle($input))->toThrow($error)
        ->and($packages->readyMade())->toHaveCount(2);
})->with([
    '6 days' => [new PackageInput('Power', ['general-power'], '10', 6), InvalidUsageDays::class],
    '366 days' => [new PackageInput('Power', ['general-power'], '10', 366), InvalidUsageDays::class],
    'negative price' => [new PackageInput('Power', ['general-power'], '-1', 30), InvalidPrice::class],
    'blank name' => [new PackageInput(' ', ['general-power'], '10', 30), InvalidCatalogueName::class],
    'no tool' => [new PackageInput('Power', [], '10', 30), PackageWithoutTools::class],
    'unknown tool' => [new PackageInput('Power', ['floor-plan-editor'], '10', 30), UnknownTool::class],
]);

it('accepts the edges of the days, 7 and 365', function (int $days): void {
    $packages = new InMemoryPackageRepository;

    $id = new CreatePackage($packages)->handle(new PackageInput('Power', ['general-power'], '0', $days));

    expect($packages->terms[$id->value]?->days->value)->toBe($days);
})->with([7, 365]);

it('changes the name, the tools and the terms of a ready-made package', function (): void {
    $packages = new InMemoryPackageRepository;

    new ChangePackage($packages)->handle(2, new PackageInput('Tester Plus', ['ohms-law', 'general-power'], '15.00', 60));

    expect($packages->get(new PackageId(2))->name)->toBe('Tester Plus')
        ->and(toolsOfPackage($packages, 2))->toContain('ohms-law', 'general-power', 'basic-calculator')
        ->and($packages->terms[2])->toBeInstanceOf(PackageTerms::class)
        ->and($packages->terms[2]?->days->value)->toBe(60);
});

it('changes the free package without terms, since it is not sold', function (): void {
    $packages = new InMemoryPackageRepository;

    new ChangePackage($packages)->handle(1, new PackageInput('Starter', ['ohms-law'], '', 0));

    expect($packages->free()->name)->toBe('Starter')
        ->and($packages->free()->kind->value)->toBe('free')
        ->and($packages->terms[1])->toBeNull();
});

it('refuses a ready-made package with days out of range and leaves it as it was', function (): void {
    $packages = new InMemoryPackageRepository;

    expect(static fn () => new ChangePackage($packages)->handle(2, new PackageInput('Tester', ['ohms-law'], '10', 400)))
        ->toThrow(InvalidUsageDays::class)
        ->and($packages->terms)->toBe([]);
});

it('does not change a custom package or one that does not exist', function (int $id): void {
    $packages = new InMemoryPackageRepository;
    $packages->customFor(new AccountId(7), [ToolId::from('ohms-law')]);

    expect(static fn () => new ChangePackage($packages)->handle($id, new PackageInput('Mine', ['ohms-law'], '1', 30)))
        ->toThrow(UnknownPackage::class);
})->with(['custom' => 4, 'missing' => 99]);

it('renames a tool, and refuses a name another tool has or a blank one', function (): void {
    $tools = new InMemoryToolRepository;
    $rename = new RenameTool($tools);

    $rename->handle('ohms-law', " Ohm's first law ");

    expect($tools->named([ToolId::from('ohms-law')])[0]->name)->toBe("Ohm's first law")
        ->and(static fn () => $rename->handle('general-power', "Ohm's first law"))->toThrow(ToolNameTaken::class)
        ->and(static fn () => $rename->handle('general-power', ''))->toThrow(InvalidCatalogueName::class)
        ->and(static fn () => $rename->handle('floor-plan-editor', 'Editor'))->toThrow(UnknownTool::class);
});

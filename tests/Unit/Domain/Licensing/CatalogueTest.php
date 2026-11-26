<?php

declare(strict_types=1);

use App\Domain\Licensing\CatalogueName;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\PackageDefinition;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\Price;
use App\Domain\Licensing\ToolId;

/*
 * The values the admin panel writes into the catalogue: names, prices and package definitions (#58).
 */

it('keeps a name without the spaces around it, up to 100 characters', function (): void {
    expect(new CatalogueName('  Tester  ')->value)->toBe('Tester')
        ->and(new CatalogueName(str_repeat('é', 100))->value)->toBe(str_repeat('é', 100));
});

it('refuses a blank name and one of 101 characters', function (string $name): void {
    expect(static fn (): CatalogueName => new CatalogueName($name))->toThrow(InvalidCatalogueName::class);
})->with(['empty' => '', 'blank' => '   ', '101 characters' => str_repeat('a', 101)]);

it('reads a price with up to two decimal places, from 0.00 to 99999.99', function (string $text, string $amount): void {
    expect((string) Price::of($text)->amount)->toBe($amount);
})->with([
    ['0', '0.00'],
    ['49.9', '49.90'],
    [' 120.50 ', '120.50'],
    ['99999.99', '99999.99'],
]);

it('refuses a negative price, a price too large, three decimal places and words', function (string $text): void {
    expect(static fn (): Price => Price::of($text))->toThrow(InvalidPrice::class);
})->with(['-1', '-0.01', '100000', '1.234', '1,50', 'free', '']);

it('adds the everyday tools to every package definition, without repeats', function (): void {
    $definition = new PackageDefinition(new CatalogueName('Tester'), [ToolId::from('ohms-law'), ToolId::from('unit-converter')]);

    expect(array_map(static fn (ToolId $tool): string => $tool->value, $definition->tools))
        ->toBe(['basic-calculator', 'unit-converter', 'rule-of-three', 'ohms-law']);
});

it('refuses a package with no tool besides the everyday ones', function (array $tools): void {
    $picked = array_map(ToolId::from(...), $tools);

    expect(static fn (): PackageDefinition => new PackageDefinition(new CatalogueName('Empty'), $picked))
        ->toThrow(PackageWithoutTools::class);
})->with(['none' => [[]], 'essentials only' => [['basic-calculator', 'rule-of-three']]]);

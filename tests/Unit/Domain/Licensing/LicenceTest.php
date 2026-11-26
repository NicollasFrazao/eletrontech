<?php

declare(strict_types=1);

use App\Domain\Account\AccountId;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageNotForSale;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use App\Domain\Licensing\UsageDays;

/**
 * A package with the id, kind and tools given.
 */
function package(int $id, PackageKind $kind, string ...$tools): Package
{
    $ids = array_map(ToolId::from(...), $tools);

    return new Package(new PackageId($id), "Package {$id}", $kind, $ids === [] ? [ToolId::from('ohms-law')] : $ids);
}

/**
 * A moment on 25 November 2026.
 */
function at(string $time): DateTimeImmutable
{
    return new DateTimeImmutable("2026-11-25 {$time}");
}

it('starts a new account with the free package for seven days', function (): void {
    $licence = Licence::initial(new AccountId(1), package(1, PackageKind::Free), at('10:00'));

    expect($licence->startsAt)->toEqual(at('10:00'))
        ->and($licence->endsAt)->toEqual(new DateTimeImmutable('2026-12-02 10:00'))
        ->and($licence->remainingDaysAt(at('10:00')))->toBe(7);
});

it('refuses another package as the initial licence and the free package in the shop', function (): void {
    $free = Licence::initial(new AccountId(1), package(1, PackageKind::Free), at('10:00'));

    expect(static fn (): Licence => Licence::initial(new AccountId(1), package(2, PackageKind::ReadyMade), at('10:00')))
        ->toThrow(PackageNotForSale::class)
        ->and(static fn (): Licence => $free->renewedWith(package(1, PackageKind::Free), new UsageDays(7), at('10:00')))
        ->toThrow(PackageNotForSale::class)
        ->and(static fn (): Licence => Licence::bought(new AccountId(1), package(1, PackageKind::Free), new UsageDays(7), at('10:00')))
        ->toThrow(PackageNotForSale::class);
});

it('extends the same package from its end while it runs', function (): void {
    $licence = Licence::bought(new AccountId(1), package(2, PackageKind::ReadyMade), new UsageDays(10), at('10:00'));

    $renewed = $licence->renewedWith(package(2, PackageKind::ReadyMade), new UsageDays(7), new DateTimeImmutable('2026-11-30 08:00'));

    expect($renewed->startsAt)->toEqual(at('10:00'))
        ->and($renewed->endsAt)->toEqual(new DateTimeImmutable('2026-12-12 10:00'));
});

it('replaces the licence from now with another package, or once it has ended', function (int $package, string $now): void {
    $licence = Licence::bought(new AccountId(1), package(2, PackageKind::ReadyMade), new UsageDays(10), at('10:00'));

    $renewed = $licence->renewedWith(package($package, PackageKind::ReadyMade), new UsageDays(7), new DateTimeImmutable($now));

    expect($renewed->startsAt)->toEqual(new DateTimeImmutable($now))
        ->and($renewed->endsAt)->toEqual(new DateTimeImmutable($now)->modify('+7 days'))
        ->and($renewed->package->id->value)->toBe($package);
})->with([
    'another package, running' => [3, '2026-11-30 08:00'],
    'the same package, ended' => [2, '2026-12-05 10:00'],
]);

it('treats a custom package bought again with other tools as another package', function (): void {
    $licence = Licence::bought(new AccountId(1), package(9, PackageKind::Custom, 'ohms-law'), new UsageDays(10), at('10:00'));

    $same = $licence->renewedWith(package(9, PackageKind::Custom, 'ohms-law'), new UsageDays(7), at('11:00'));
    $other = $licence->renewedWith(package(9, PackageKind::Custom, 'ohms-law', 'general-power'), new UsageDays(7), at('11:00'));

    expect($same->endsAt)->toEqual(new DateTimeImmutable('2026-12-12 10:00'))
        ->and($other->startsAt)->toEqual(at('11:00'));
});

it('counts a part of a day as a day and nothing once it has ended', function (string $now, int $days): void {
    $licence = Licence::bought(new AccountId(1), package(2, PackageKind::ReadyMade), new UsageDays(7), at('10:00'));

    expect($licence->remainingDaysAt(new DateTimeImmutable($now)))->toBe($days);
})->with([
    ['2026-11-25 10:00', 7],
    ['2026-11-25 10:01', 7],
    ['2026-12-01 10:00', 1],
    ['2026-12-02 09:59', 1],
    ['2026-12-02 10:00', 0],
    ['2027-01-01 00:00', 0],
]);

it('allows a tool of its package while it runs, and nothing else', function (): void {
    $licence = Licence::bought(new AccountId(1), package(2, PackageKind::ReadyMade, 'ohms-law'), new UsageDays(7), at('10:00'));

    expect($licence->allows(ToolId::from('ohms-law'), at('10:00')))->toBeTrue()
        ->and($licence->allows(ToolId::from('ohms-law'), new DateTimeImmutable('2026-12-02 09:59:59')))->toBeTrue()
        ->and($licence->allows(ToolId::from('ohms-law'), new DateTimeImmutable('2026-12-02 10:00')))->toBeFalse()
        ->and($licence->allows(ToolId::from('ohms-law'), at('09:59')))->toBeFalse()
        ->and($licence->allows(ToolId::from('general-power'), at('10:00')))->toBeFalse();
});

it('accepts from 7 to 365 days of use', function (int $days): void {
    expect((new UsageDays($days))->value)->toBe($days);
})->with([7, 30, 365]);

it('refuses fewer than 7 or more than 365 days', function (int $days): void {
    new UsageDays($days);
})->with([0, 6, 366, -7])->throws(InvalidUsageDays::class);

it('reads a tool identifier and refuses what cannot be one', function (): void {
    expect(ToolId::from('series-parallel-resistance')->value)->toBe('series-parallel-resistance')
        ->and(static fn (): ToolId => ToolId::from('Ohms law'))->toThrow(UnknownTool::class)
        ->and(static fn (): ToolId => ToolId::from('-ohms'))->toThrow(UnknownTool::class)
        ->and(static fn (): ToolId => ToolId::from(str_repeat('a', 51)))->toThrow(UnknownTool::class)
        ->and(static fn (): PackageId => new PackageId(0))->toThrow(UnknownPackage::class);
});

it('keeps each tool of a package once, in order', function (): void {
    $package = package(2, PackageKind::ReadyMade, 'ohms-law', 'general-power', 'ohms-law');

    expect(array_map(static fn (ToolId $tool): string => $tool->value, $package->tools()))->toBe(['ohms-law', 'general-power'])
        ->and($package->isSameAs(package(2, PackageKind::ReadyMade, 'ohms-law', 'general-power')))->toBeTrue()
        ->and($package->isSameAs(package(3, PackageKind::ReadyMade, 'ohms-law', 'general-power')))->toBeFalse();
});

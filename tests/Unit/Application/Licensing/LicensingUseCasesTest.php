<?php

declare(strict_types=1);

use App\Application\Licensing\GrantInitialLicence;
use App\Application\Licensing\PackageView;
use App\Application\Licensing\RecordToolUse;
use App\Application\Licensing\ShowLicence;
use App\Application\Licensing\ShowShop;
use App\Application\Licensing\ToolAccess;
use App\Domain\Account\AccountId;
use App\Domain\Account\Activity;
use App\Domain\Account\ActivityType;
use App\Domain\Account\WrongPassword;
use App\Domain\Licensing\EmptyCustomPackage;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageNotForSale;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\Tool;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use Tests\Support\Licensing\InMemoryToolUseLog;
use Tests\Support\Licensing\LicensingKit;

it('signs a person up with the free package for seven days', function (): void {
    $kit = new LicensingKit;

    $id = $kit->signUp();

    $licence = $kit->licences->forHolder($id);
    expect($licence?->package->name)->toBe('Basic')
        ->and($licence?->remainingDaysAt($kit->accounts->clock->now()))->toBe(7);
});

it('buys a ready-made package, replacing the free one, and records the purchase', function (): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();

    $licence = $kit->buy($id, 2, 30);

    expect($licence->package->name)->toBe('Tester')
        ->and($kit->licences->forHolder($id))->toEqual($licence)
        ->and($licence->remainingDaysAt($kit->accounts->clock->now()))->toBe(30)
        ->and(array_map(static fn (Activity $activity): ActivityType => $activity->type, $kit->accounts->activity->latest($id, 1)))
        ->toBe([ActivityType::PackageBought]);
});

it('extends the package bought again while it runs', function (): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();
    $kit->buy($id, 2, 30);
    $kit->accounts->clock->advance('+10 days');

    $licence = $kit->buy($id, 2, 7);

    expect($licence->remainingDaysAt($kit->accounts->clock->now()))->toBe(27);
});

it('buys a person a license without one, as an account from before E5 has', function (): void {
    $kit = new LicensingKit;
    $id = $kit->accounts->registered();

    expect($kit->buy($id, 3, 7)->package->name)->toBe('Golden');
});

it('builds a custom package from the tools picked, and extends it while the same tools are bought', function (): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();

    $first = $kit->buy($id, null, 10, ['ohms-law', 'general-power', 'ohms-law']);
    $same = $kit->buy($id, null, 10, ['ohms-law', 'general-power']);
    $other = $kit->buy($id, null, 10, ['unit-converter']);

    expect($first->package->name)->toBe('Custom')
        ->and(count($first->package->tools()))->toBe(2)
        ->and($same->remainingDaysAt($kit->accounts->clock->now()))->toBe(20)
        ->and($other->remainingDaysAt($kit->accounts->clock->now()))->toBe(10)
        ->and($other->package->id)->toEqual($first->package->id);
});

it('refuses a purchase and keeps the licence', function (?int $package, int $days, array $tools, string $password, string $error): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();
    $before = $kit->licences->forHolder($id);
    $kit->buy($id, null, 7, ['general-power']);
    $kit->licences->save($before ?? throw new LogicException('No initial licence.'));

    expect(static fn (): Licence => $kit->buy($id, $package, $days, $tools, $password))->toThrow($error)
        ->and($kit->licences->forHolder($id))->toEqual($before);
})->with([
    'wrong password' => [2, 30, [], 'wrong horse', WrongPassword::class],
    'too few days' => [2, 6, [], 'correct horse', InvalidUsageDays::class],
    'too many days' => [2, 366, [], 'correct horse', InvalidUsageDays::class],
    'unknown package' => [99, 30, [], 'correct horse', UnknownPackage::class],
    'the free package' => [1, 30, [], 'correct horse', PackageNotForSale::class],
    'a custom package by its id' => [4, 30, [], 'correct horse', UnknownPackage::class],
    'a custom package without tools' => [null, 30, [], 'correct horse', EmptyCustomPackage::class],
    'a tool that does not exist' => [null, 30, ['time-machine'], 'correct horse', UnknownTool::class],
    'a malformed tool' => [null, 30, ['Time Machine'], 'correct horse', UnknownTool::class],
]);

it('allows a tool of the running licence only', function (): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();

    expect($kit->access($id, 'basic-calculator'))->toBe(ToolAccess::Allowed)
        ->and($kit->access($id, 'ohms-law'))->toBe(ToolAccess::NotLicensed)
        ->and($kit->access($id, 'time-machine'))->toBe(ToolAccess::UnknownTool)
        ->and($kit->access($id, '../etc'))->toBe(ToolAccess::UnknownTool)
        ->and($kit->access(new AccountId(99), 'basic-calculator'))->toBe(ToolAccess::NotLicensed);

    $kit->accounts->clock->advance('+7 days');

    expect($kit->access($id, 'basic-calculator'))->toBe(ToolAccess::NotLicensed);
});

it('records a use of a tool, now', function (): void {
    $kit = new LicensingKit;
    $log = new InMemoryToolUseLog;

    new RecordToolUse($log, $kit->accounts->clock)->handle(new AccountId(4), 'ohms-law');

    expect($log->uses)->toEqual([[4, 'ohms-law', $kit->accounts->clock->now()]]);
});

it('shows the licence with its days left and tools, and nothing without one', function (): void {
    $kit = new LicensingKit;
    $id = $kit->signUp();
    $kit->accounts->clock->advance('+2 days +1 hour');
    $show = new ShowLicence($kit->licences, $kit->tools, $kit->accounts->clock);

    $view = $show->handle($id);

    expect($view?->licence->package->name)->toBe('Basic')
        ->and($view?->remainingDays)->toBe(5)
        ->and(array_map(static fn (Tool $tool): string => $tool->name, $view->tools ?? []))->toBe(['Basic calculator', 'Unit converter'])
        ->and($show->handle(new AccountId(99)))->toBeNull();
});

it('shows the ready-made packages, one of them, and every tool for a custom package', function (): void {
    $kit = new LicensingKit;
    $shop = new ShowShop($kit->packages, $kit->tools);

    expect(array_map(static fn (PackageView $package): string => $package->name, $shop->packages()))->toBe(['Golden', 'Tester'])
        ->and($shop->package(2)->tools[0]->name)->toBe('Ohms law')
        ->and(count($shop->tools()))->toBe(4)
        ->and(static fn (): PackageView => $shop->package(1))->toThrow(UnknownPackage::class);
});

it('refuses to start a licence with a free package that is not free, and a malformed tool use', function (): void {
    $kit = new LicensingKit;
    $packages = new readonly class($kit->packages) implements PackageRepository
    {
        public function __construct(private PackageRepository $inner) {}

        public function get(PackageId $id): Package
        {
            return $this->inner->get($id);
        }

        public function free(): Package
        {
            return $this->inner->get(new PackageId(2));
        }

        public function readyMade(): array
        {
            return $this->inner->readyMade();
        }

        public function customFor(AccountId $owner, array $tools): Package
        {
            return $this->inner->customFor($owner, $tools);
        }
    };

    expect(static fn () => new GrantInitialLicence($packages, $kit->licences, $kit->accounts->clock)->handle(new AccountId(1)))
        ->toThrow(UnexpectedValueException::class)
        ->and(static fn () => new RecordToolUse(new InMemoryToolUseLog, $kit->accounts->clock)->handle(new AccountId(1), 'Bad Id'))
        ->toThrow(InvalidArgumentException::class);
});

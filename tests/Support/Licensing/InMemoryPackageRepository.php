<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageDefinition;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\PackageTerms;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use LogicException;

/**
 * Packages kept in memory: 1 Basic (free: basic-calculator, unit-converter), 2 Tester (ohms-law) and 3 Golden
 * (every tool); custom packages get the next ids.
 */
final class InMemoryPackageRepository implements PackageRepository
{
    /**
     * @var array<int, Package> Keyed by id.
     */
    private array $packages = [];

    /**
     * @var array<int, int> The custom package id of each owner.
     */
    private array $customOf = [];

    /**
     * @var array<int, PackageTerms|null> The terms of each package added or changed, keyed by id.
     */
    public array $terms = [];

    public function __construct(private readonly ToolRepository $tools = new InMemoryToolRepository)
    {
        $this->put('Basic', PackageKind::Free, 'basic-calculator', 'unit-converter');
        $this->put('Tester', PackageKind::ReadyMade, 'ohms-law');
        $this->put('Golden', PackageKind::ReadyMade, 'ohms-law', 'unit-converter', 'general-power', 'basic-calculator');
    }

    public function get(PackageId $id): Package
    {
        return $this->packages[$id->value] ?? throw UnknownPackage::withId($id->value);
    }

    public function free(): Package
    {
        return $this->packages[1];
    }

    public function readyMade(): array
    {
        $packages = array_values(array_filter(
            $this->packages,
            static fn (Package $package): bool => $package->kind === PackageKind::ReadyMade,
        ));
        usort($packages, static fn (Package $a, Package $b): int => strcmp($a->name, $b->name));

        return $packages;
    }

    public function customFor(AccountId $owner, array $tools): Package
    {
        $this->checkTools($tools);

        $id = new PackageId($this->customOf[$owner->value] ?? count($this->packages) + 1);
        $this->customOf[$owner->value] = $id->value;

        return $this->packages[$id->value] = new Package($id, 'Custom', PackageKind::Custom, $tools);
    }

    public function add(PackageDefinition $definition, PackageTerms $terms): PackageId
    {
        $this->checkTools($definition->tools);
        $id = new PackageId(count($this->packages) + 1);
        $this->packages[$id->value] = new Package($id, $definition->name->value, PackageKind::ReadyMade, $definition->tools);
        $this->terms[$id->value] = $terms;

        return $id;
    }

    public function change(PackageId $id, PackageDefinition $definition, ?PackageTerms $terms): void
    {
        $package = $this->get($id);

        if ($package->kind === PackageKind::Custom) {
            throw UnknownPackage::withId($id->value);
        }

        $this->checkTools($definition->tools);
        $this->packages[$id->value] = new Package($id, $definition->name->value, $package->kind, $definition->tools);
        $this->terms[$id->value] = $terms;
    }

    /**
     * @param  list<ToolId>  $tools
     */
    private function checkTools(array $tools): void
    {
        foreach ($tools as $tool) {
            if (! $this->tools->exists($tool)) {
                throw UnknownTool::withId($tool->value);
            }
        }
    }

    /**
     * Adds a package under the next id.
     */
    private function put(string $name, PackageKind $kind, string ...$tools): void
    {
        $ids = array_map(ToolId::from(...), $tools);

        if ($ids === []) {
            throw new LogicException('A package needs a tool.');
        }

        $id = count($this->packages) + 1;
        $this->packages[$id] = new Package(new PackageId($id), $name, $kind, $ids);
    }
}

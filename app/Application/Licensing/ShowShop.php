<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\Tool;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownPackage;

/**
 * What the shop shows: the ready-made packages with their tools, one package in detail, and every tool
 * for a custom package.
 */
final readonly class ShowShop
{
    public function __construct(
        private PackageRepository $packages,
        private ToolRepository $tools,
    ) {}

    /**
     * The ready-made packages, by name.
     *
     * @return list<PackageView>
     */
    public function packages(): array
    {
        return array_map($this->view(...), $this->packages->readyMade());
    }

    /**
     * One ready-made package ("see more").
     *
     * @throws UnknownPackage When no ready-made package has the id.
     */
    public function package(int $id): PackageView
    {
        $package = $this->packages->get(new PackageId($id));

        if ($package->kind !== PackageKind::ReadyMade) {
            throw UnknownPackage::withId($id);
        }

        return $this->view($package);
    }

    /**
     * Every tool, by name, for a custom package.
     *
     * @return list<Tool>
     */
    public function tools(): array
    {
        return $this->tools->all();
    }

    /**
     * The package with the names of its tools.
     */
    private function view(Package $package): PackageView
    {
        return new PackageView($package->id->value, $package->name, $this->tools->named($package->tools()));
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;

/**
 * Changes a free or ready-made package from the admin panel: its name, its tools and, for a ready-made
 * one, its price and days. The free package is not sold, so the price and the days given for it are
 * not read; a person's custom package is theirs and is not changed here.
 *
 * A running licence sees the new tools at once, since it holds the package, not a copy of its tools.
 */
final readonly class ChangePackage
{
    public function __construct(
        private PackageRepository $packages,
    ) {}

    /**
     * Checks every value, then stores the package; a refused value changes nothing.
     *
     * @throws UnknownPackage When no free or ready-made package has the id.
     * @throws InvalidCatalogueName When the name is blank or too long.
     * @throws UnknownTool When a tool does not exist.
     * @throws PackageWithoutTools When no tool besides the everyday ones was chosen.
     * @throws InvalidPrice When the price of a ready-made package is not an amount from 0.00 to 99,999.99.
     * @throws InvalidUsageDays When the days of a ready-made package are outside 7 to 365.
     */
    public function handle(int $id, PackageInput $input): void
    {
        $package = $this->packages->get(new PackageId($id));

        if ($package->kind === PackageKind::Custom) {
            throw UnknownPackage::withId($id);
        }

        $definition = $input->definition();
        $terms = $package->kind === PackageKind::Free ? null : $input->terms();

        $this->packages->change($package->id, $definition, $terms);
    }
}

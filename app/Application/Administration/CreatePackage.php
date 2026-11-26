<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\UnknownTool;

/**
 * Adds a ready-made package to the shop from the admin panel. Only ready-made packages are made here:
 * there is one free package, from the migrations, and a person makes their own custom one in the shop.
 */
final readonly class CreatePackage
{
    public function __construct(
        private PackageRepository $packages,
    ) {}

    /**
     * Checks every value, then stores the package; a refused value stores nothing.
     *
     * @throws InvalidCatalogueName When the name is blank or too long.
     * @throws UnknownTool When a tool does not exist.
     * @throws PackageWithoutTools When no tool besides the everyday ones was chosen.
     * @throws InvalidPrice When the price is not an amount from 0.00 to 99,999.99.
     * @throws InvalidUsageDays When the days are outside 7 to 365.
     */
    public function handle(PackageInput $input): PackageId
    {
        $definition = $input->definition();
        $terms = $input->terms();

        return $this->packages->add($definition, $terms);
    }
}

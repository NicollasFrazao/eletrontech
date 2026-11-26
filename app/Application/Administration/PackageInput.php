<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Licensing\CatalogueName;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\PackageDefinition;
use App\Domain\Licensing\PackageTerms;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\Price;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\UnknownTool;
use App\Domain\Licensing\UsageDays;

/**
 * A package as the admin panel's form sends it: the name, the tools chosen, the price and the days.
 */
final readonly class PackageInput
{
    /**
     * @param  list<string>  $tools  The identifiers of the tools chosen besides the everyday ones.
     * @param  string  $price  In reais, with a dot before the cents ("49.90").
     * @param  int  $days  The days of use the price is for.
     */
    public function __construct(
        public string $name,
        public array $tools,
        public string $price,
        public int $days,
    ) {}

    /**
     * The name and the tools, with the everyday tools added.
     *
     * @throws InvalidCatalogueName When the name is blank or too long.
     * @throws UnknownTool When an identifier cannot be a tool's.
     * @throws PackageWithoutTools When no tool besides the everyday ones was chosen.
     */
    public function definition(): PackageDefinition
    {
        return new PackageDefinition(new CatalogueName($this->name), array_map(ToolId::from(...), $this->tools));
    }

    /**
     * The price and the days.
     *
     * @throws InvalidPrice When the price is not an amount from 0.00 to 99,999.99.
     * @throws InvalidUsageDays When the days are outside 7 to 365.
     */
    public function terms(): PackageTerms
    {
        return new PackageTerms(Price::of($this->price), new UsageDays($this->days));
    }
}

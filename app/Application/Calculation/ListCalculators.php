<?php

declare(strict_types=1);

namespace App\Application\Calculation;

use App\Domain\Calculator\CalculatorCatalog;

/**
 * Lists the calculators with the schema of their inputs.
 */
final readonly class ListCalculators
{
    public function __construct(
        private CalculatorCatalog $calculators,
        private DescribeCalculator $describe,
    ) {}

    /**
     * Every calculator, in catalogue order, with its inputs in the order a form shows them.
     */
    public function handle(): CalculatorList
    {
        return new CalculatorList(array_map($this->describe->describe(...), $this->calculators->all()));
    }
}

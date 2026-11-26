<?php

declare(strict_types=1);

namespace App\Application\Calculation;

use App\Application\UnitConversion\UnitOption;
use App\Domain\Calculator\Calculator;
use App\Domain\Calculator\CalculatorCatalog;
use App\Domain\Calculator\InputField;
use App\Domain\Calculator\UnknownCalculator;
use App\Domain\Quantity\Unit;
use App\Domain\Quantity\UnitCatalogue;

/**
 * Describes one calculator with the schema of its inputs, for the form that runs it.
 */
final readonly class DescribeCalculator
{
    public function __construct(
        private CalculatorCatalog $calculators,
        private UnitCatalogue $units,
    ) {}

    /**
     * The calculator with this identifier, described.
     *
     * @throws UnknownCalculator
     */
    public function handle(string $id): CalculatorDescription
    {
        return $this->describe($this->calculators->find($id));
    }

    /**
     * A calculator's description, with its inputs in the order a form shows them.
     */
    public function describe(Calculator $calculator): CalculatorDescription
    {
        return new CalculatorDescription(
            $calculator->id(),
            $calculator->group()->value,
            array_map($this->input(...), $calculator->inputs()),
        );
    }

    /**
     * The description of one input.
     */
    private function input(InputField $field): InputDescription
    {
        $default = $field->defaultUnit;
        $units = $default instanceof Unit ? $this->units->ofDimension($default->dimension()) : [];

        return new InputDescription(
            $field->name,
            $field->kind()->value,
            $field->isRequired,
            new AcceptedValues(
                $default instanceof Unit ? UnitOption::of($default) : null,
                array_map(UnitOption::of(...), $units),
                $field->options,
            ),
        );
    }
}

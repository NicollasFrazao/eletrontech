<?php

declare(strict_types=1);

namespace App\Application\Calculation;

/**
 * The calculators the application offers, each with its input schema.
 */
final readonly class CalculatorList
{
    /**
     * @param  list<CalculatorDescription>  $calculators  In catalogue order.
     */
    public function __construct(
        public array $calculators,
    ) {}

    /**
     * The calculators under their group, the groups in the order their first calculator appears.
     *
     * @return array<string, non-empty-list<CalculatorDescription>> Keyed by group, such as "electrical".
     */
    public function byGroup(): array
    {
        $groups = [];

        foreach ($this->calculators as $calculator) {
            $groups[$calculator->group][] = $calculator;
        }

        return $groups;
    }
}

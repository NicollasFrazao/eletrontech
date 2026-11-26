<?php

declare(strict_types=1);

namespace App\Domain\Calculator;

/**
 * The field a calculator belongs to, as the tools are offered: the 2015 version split them the same way.
 */
enum CalculatorGroup: string
{
    /** Circuits, loads, lighting and wiring. */
    case Electrical = 'electrical';

    /** Areas, perimeters, surface areas and volumes of figures. */
    case Geometry = 'geometry';

    /** Arithmetic, number bases and proportions. */
    case Mathematics = 'mathematics';

    /** Fluids and other physics outside electricity. */
    case Physics = 'physics';

    /** Materials and air conditioning for a building. */
    case Construction = 'construction';
}

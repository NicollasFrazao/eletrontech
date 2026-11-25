<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * The values of an event a person types, named as the form names them.
 */
enum EventField: string
{
    case Title = 'title';
    case Moment = 'starts_at';
    case Description = 'description';
}

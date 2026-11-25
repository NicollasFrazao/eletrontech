<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * One entry of a person's agenda.
 */
final readonly class Event
{
    public function __construct(
        public EventId $id,
        public EventDetails $details,
    ) {}
}

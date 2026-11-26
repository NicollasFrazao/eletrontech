<?php

declare(strict_types=1);

namespace App\Application\Agenda;

/**
 * An event as the form sends it: title, date and time ("YYYY-MM-DD HH:MM") and description.
 */
final readonly class EventInput
{
    public function __construct(
        public string $title,
        public string $startsAt,
        public string $description,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * The identity of an event: a positive integer the storage assigns.
 */
final readonly class EventId
{
    /**
     * @throws EventNotFound When the value is not positive, so no event can have it.
     */
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw EventNotFound::withId($value);
        }
    }
}

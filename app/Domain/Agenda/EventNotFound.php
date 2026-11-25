<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * The event does not exist or is someone else's.
 */
final class EventNotFound extends AgendaError
{
    /**
     * The error for the id.
     */
    public static function withId(int $id): self
    {
        return new self("No event of this person has the id {$id}.");
    }
}

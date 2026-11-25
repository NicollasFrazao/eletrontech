<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * A value typed for an event is not valid; the field says which.
 */
final class InvalidEvent extends AgendaError
{
    private function __construct(
        public readonly EventField $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * The error for one field.
     */
    public static function of(EventField $field, string $reason): self
    {
        return new self($field, "Invalid {$field->value}: {$reason}.");
    }
}
